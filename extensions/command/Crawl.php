<?php

namespace li3_simplesearch\extensions\command;

use li3_simplesearch\extensions\service\Crawler;

/**
 * Simple web crawler. Recursively crawls links from a base URL.
 *
 * @package default
 * @author John Anderson
 */
class Crawl extends \lithium\console\Command {
	/**
	* Crawler instance.
	*
	* @var string
	*/
	protected $crawler = null;

	/**
	* Main shell logic.
	*
	* @return void
	* @author John Anderson
*/
	public function run() {
		if(!isset($this->url) || empty($this->url)) {
			$this->out();
			$this->out("{:error}Error:{:end} Please supply a URL to crawl.");
			return $this->usage();
		}
		extract(parse_url($this->url));
		if(!isset($host) || empty($host)) {
			$this->out();
			$this->out("{:error}Error:{:end} The supplied URL must be an absolute URL that contains a host.");
			return $this->usage();
		}
		if(!isset($path) || empty($path)) {
			$path = '/';
		}
		$this->crawler = new Crawler($this->url, $this);
		$links = $this->crawler->run();
	}

	protected function usage() {
		$this->out();
		$this->out('Usage:   {:purple}li3 crawl{:end} {:cyan}--url={:end}{:green}[URL]{:end}');
		$this->out('Example: li3 crawl --url=http://example.com');
		$this->out();
	}
}