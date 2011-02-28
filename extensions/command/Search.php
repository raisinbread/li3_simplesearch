<?php

namespace li3_simplesearch\extensions\command;

use li3_simplesearch\models\Page;
use li3_simplesearch\models\Term;
use li3_simplesearch\extensions\util\Stemmer;

/**
 * Query shell task for matching search queries to the indexed pages and terms.
 *
 * @package default
 * @author John Anderson
 */
class Search extends \lithium\console\Command {
	/**
	 * Main shell logic.
	 *
	 * @return void
	 * @author John Anderson
	 */
	public function run() {
		$args = func_get_args();
		if(count($args) < 1 || isset($this->stats)) {
			$this->stats();
		} else {
			if(count($args) > 1) {
				$query = implode(" ", $args);
			} else {
				$query = $args[0];
			}
			$stemmer = new Stemmer();
			$queryStems = $stemmer->string($query);
			
			$totalDocuments = Page::find('count');
			$results = Term::findRankedByTerms($queryStems, $totalDocuments);
			
			$this->out();
			$this->out("Query: {:purple}$query{:end}");
			
			$this->displayResults($results);
		}
	}
	
	/**
	 * Outputs basic index statistics.
	 *
	 * @return void
	 * @author John Anderson
	 */
	public function stats() {
		$count = Page::find('count');
		$this->out();
		$this->out("Total indexed documents: {:cyan}$count{:end}");
		$this->out();
		$this->out("To re-index, use {:purple}li3 crawl{:end} {:cyan}--url={:end}{:green}[URL]{:end}");
		$this->out();
	}
	
	/**
	 * Outputs ranked query results.
	 *
	 * @param string $results 
	 * @return void
	 * @author John Anderson
	 */
	public function displayResults($results) {
		$numResults = count($results);
		if($numResults > 0) {
			$this->out("{:green}$numResults{:end} results returned. Top 15 results:");
			for($i = 0; $i < 15; $i++) {
				$num = $i + 1;
				$line = str_pad("$num. ", 4, ' ', STR_PAD_RIGHT);
				$this->out("{:cyan}$line{:end}" . $results[$i]['url']);
			}
		} else {
			$this->out("{:error}No results returned.{:end}");
		}
		$this->out();
	}
}

?>