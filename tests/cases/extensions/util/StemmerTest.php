<?php 

namespace li3_simplesearch\tests\cases\extensions\util;

use li3_simplesearch\extensions\util\Stemmer;

/**
 * Stemmer test.
 *
 * @package default
 * @author John Anderson
 */
class StemmerTest extends \lithium\test\Unit {
	
	/**
	 * File pointer to input terms.
	 *
	 * @var string
	 */
	protected $vocabularyHandle;
	
	/**
	 * File pointer to expected output terms.
	 *
	 * @var string
	 */
	protected $outputHandle;
	
	/**
	 * Stemmer instance.
	 *
	 * @var string
	 */
	protected $stemmer;
	
	/**
	 * Test case initialization.
	 *
	 * @return void
	 * @author John Anderson
	 */
	public function setUp() {
		$testsDir = dirname(dirname(dirname(dirname(__FILE__))));
		$dataDir = $testsDir . '/data';
		$this->vocabularyHandle = fopen($dataDir . '/voc.txt', 'r');
		$this->outputHandle     = fopen($dataDir . '/output.txt', 'r');
		$this->stemmer = new Stemmer();
	}
	
	/**
	 * Main test logic.
	 *
	 * @return void
	 * @author John Anderson
	 */
	public function testStemming() {
		do {
			$input     = fgets($this->vocabularyHandle);
			$processed = $this->stemmer->word($input);
			$expected  = fgets($this->outputHandle);
			$this->assertEqual(trim($expected), trim($processed));
		} while ($input && $expected);
	}
}