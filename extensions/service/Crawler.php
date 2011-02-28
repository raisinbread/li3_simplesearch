<?php

namespace li3_simplesearch\extensions\service;

use \lithium\net\http\Service;
use li3_simplesearch\models\Page;
use li3_simplesearch\models\Term;
use li3_simplesearch\extensions\util\Stemmer;

/**
 * Simple web crawler.
 *
 * @package default
 * @author John Anderson
 */
class Crawler {

	/**
	 * Current URL.
	 *
	 * @var string
	 */
	protected $url = '';
	
	/**
	 * Placeholder for now. Object with an out() method
	 * that the crawler can use to send notification messages
	 * for output.
	 *
	 * @var string
	 */
	protected $delegate = null;

	/**
	* Constructor.
	*
	* @param array $config 
	* @author John Anderson
	*/
	public function __construct($url, $delegate) {
		$this->url['raw'] = $url;
		$this->url['parts'] = parse_url($url);
		$this->delegate = $delegate;
	}

	/**
	* Clears index and crawls the supplied URL.
	*
	* @return void
	* @author John Anderson
	*/
	public function run() {
		
		//Clear table:
		Page::connection()->delete('DELETE from pages');
		$urlRoot = $this->process($this->url['raw']);
	}

	/**
	* Fetches the page, scrapes it for links, and creates the
	* related database records.
	*
	* @param string $string 
	* @return void
	* @author John Anderson
	*/
	protected function process($rawURL) {
		$page = Page::find('first', array(
			'conditions' => array(
				'url' => $rawURL,
			),
		));
		
		if($page === null) {

			$this->delegate->out("Processing {:cyan}$rawURL{:end}...");
			$string = $this->fetch($rawURL);
			$data = array(
				'links'   => $this->extractLinks($string),
				'content' => $string,
			);

			$page = Page::create();
			$page->created = date('Y-m-d H:i:s');
			$page->modified = date('Y-m-d H:i:s');
			$page->url = $rawURL;
			$page->content = $data['content'];
			$page->save();

			$this->generateStems($string, $page);

			foreach($data['links'] as $link) {
				if(strstr($link, $this->url['parts']['host']) === false) {
					$link = $this->url['raw'] . $link;
				}
				$this->process($link);
			}
		}
		
		return $page;
	}

	/**
	* Provides the stemming and term indexing for a given page.
	*
	* @param string $string 
	* @return void
	* @author John Anderson
	* @see http://forge.mysql.com/wiki/MySQL_Internals_Algorithms#Full-text_Search
	*/
	public function generateStems($string, $page) {

		$stemmer = new Stemmer();
		$stems = $stemmer->html($string);
		
		$uniqueStems = array();
		foreach($stems as $stem) {
			$uniqueStems[$stem][] = '1';
		}
		
		$dtfs = array();
		$sumDtf = 0;
		
		foreach($uniqueStems as $stem => $instances) {
			$dtf = log(count($instances)) + 1;
			$sumDtf += $dtf;
			$dtfs[$stem] = $dtf;
		}

		foreach($uniqueStems as $stem => $instances) {
			$term = Term::create();
			$term->created = date('Y-m-d H:i:s');
			$term->modified = date('Y-m-d H:i:s');
			$term->term = $stem;
			$term->page_id = $page->id;
			$term->base = $dtfs[$stem]/$sumDtf;
			$term->normalization = count($uniqueStems)/(1 + 0.0115 * count($uniqueStems));
			$term->save();
		}
	}

	/**
	* Placeholder for fetching page content.
	*
	* @param string $url 
	* @return void
	* @author John Anderson
	*/
	public function fetch($url) {
		//TODO: move to \lithium\net\http\Service
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	/**
	* Returns URLs linked to from the supplied string.
	*
	* @param string $string 
	* @param string $onlyLocalDomain 
	* @return void
	* @author John Anderson
*/
	public function extractLinks($string, $onlyLocalDomain = true) {
		preg_match_all("/<a(?:[^>]*)href=\"([^\"]*)\"(?:[^>]*)>(?:[^<]*)<\/a>/is", $string, $matches);
		if(!$onlyLocalDomain) {
			return $matches[1];
		} else {
			$localLinks = array();
			foreach($matches[1] as $link) {
				extract(parse_url($link));
				if(!isset($host)) {
					$host = '';
				}
				if($this->hostIsLocal($host)) {
					$localLinks[] = $link;
				}
			}
			return $localLinks;
		}
	}

	/**
	* Determines if a link is outside the domain
	* being crawled.
	*
	* @param string $host 
	* @return void
	* @author John Anderson
*/
	protected function hostIsLocal($host) {
		//TODO: also check for www. in either?
		return empty($host) || $host == $this->url['parts']['host'];
	}
}