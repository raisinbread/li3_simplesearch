<?php

namespace li3_simplesearch\extensions\util;

/**
 * PHP 5.3 implementation of the Porter Stemming Algorithm (v2)
 * 
 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
 * @package default
 * @author John Anderson
 */
class Stemmer {
	
	/**
	 * Current word.
	 *
	 * @var string
	 */
	protected $currentWord = '';
	
	/**
	 * Current stem state.
	 *
	 * @var string
	 */
	protected $currentStem = '';
	
	/**
	 * Returns the stems from the supplied HTML string.
	 *
	 * @param string $html 
	 * @return array
	 * @author John Anderson
	 */
	public function html($html) {
		return $this->string(strip_tags($html));
	}
	
	/**
	 * Returns the stems from the supplied plain text
	 * string.
	 *
	 * @param string $string 
	 * @return array
	 * @author John Anderson
	 */
	public function string($string) {
		$string = preg_replace('/\W/', ' ', $string);
		$words = preg_split('/\s/', $string);
		$stems = array();
		foreach($words as $word) {
			if(!$word) {
				continue;
			}
			$stems[$word] = $this->_process($word);
		}
		return $stems;
	}
	
	/**
	 * Returns the stem for the given word.
	 *
	 * @param string $word 
	 * @return string
	 * @author John Anderson
	 */
	public function word($word) {
		return $this->_process($word);
	}
	
	/**
	 * Main stemming logic handler. Processes the given word
	 * with the Porter steps to result in the stem.
	 *
	 * @param string $word 
	 * @return string
	 * @author John Anderson
	 */
	protected function _process($word) {
		$word = strtolower(trim($word));
		
		//Exceptional forms
		$exceptions = array(
			'skis'   => 'ski',
			'skies'  => 'sky',
			'dying'  => 'die',
			'lying'  => 'lie',
			'tying'  => 'tie',
			'idly'   => 'idl',
			'gently' => 'gentl',
			'ugly'   => 'ugli',
			'early'  => 'earli',
			'only'   => 'onli',
			'singly' => 'singl',
			'sky'    => 'sky',
			'news'   => 'news',
			'howe'   => 'howe',
			'atlas'  => 'cosmos',
			'bias'   => 'bias',
			'andes'  => 'andes'
		);
		if(isset($exceptions[$word])) {
			return $word;
		}

		if(strlen($word) <= 2) {
			return $word;
		}
		
		if($word[0] == "'") {
			$word = substr($word, 1);
		}
		
		$word = preg_replace('/^y/', 'Y', $word);
		$word = preg_replace('/([aeiou])y/', '$1Y', $word);
		
		$this->currentWord = $word;
		$this->currentStem = $this->currentWord;

		$this->step0();
		$this->step1();
		$this->step2();
		$this->step3();
		$this->step4();
		$this->step5();
		
		return $this->currentStem;
	}
	
	/**
	 * Processes the current stem with the 0th step
	 * of the Porter algorithm.
	 *
	 * @return void
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	protected function step0() {
		$this->currentStem = $this->performOnLongestSuffix($this->currentStem, array(
			"'"   => function($word) {
				return preg_replace("/'$/", '', $word);
			},
			"'s"  => function($word) {
				return preg_replace("/'s$/", '', $word);
			},
			"'s'" => function($word) {
				return preg_replace("/'s'$/", '', $word);
			},
		));
	}
	
	/**
	 * Processes the current stem with the 1st step
	 * of the Porter algorithm.
	 *
	 * @return void
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	protected function step1() {
		// a:
		$this->currentStem = $this->performOnLongestSuffix($this->currentStem, array(
			"sses" => function($word) {
				return preg_replace("/sses$/", 'ss', $word);
			},
			"ied"  => function($word) {
				if(strlen($word) > 4) {
					return preg_replace("/ied$/", 'i', $word);
				} else {
					return preg_replace("/ied$/", 'ie', $word);
				}
			},
			"ies"  => function($word) {
				if(strlen($word) > 4) {
					return preg_replace("/ies$/", 'i', $word);
				} else {
					return preg_replace("/ies$/", 'ie', $word);
				}
			},
			"s" => function($word) {
				if(strlen($word) > 2 && preg_match('/[aeiouy].+s$/', substr($word, 0, strlen($word) - 1)) < 1) {
					return preg_replace("/s$/", '', $word);
				} else {
					return $word;
				}
			},
			"us" => function($word) {
				return $word;
			},
			"ss" => function($word) {
				return $word;
			}
		));
		
		//Exceptional forms
		$exceptions = array(
			'inning',
			'outing',
			'canning',
			'herring',
			'earring',
			'proceed',
			'exceed',
			'succeed',
		);
		if(isset($exceptions[$this->currentStem])) {
			return $exceptions[$this->currentStem];
		}
		
		// b:
		$this->currentStem = $this->performOnLongestSuffix($this->currentStem, array(
			"eed" => function($word) {
				if(strstr(Stemmer::getR1($word), 'eed')) {
					return preg_replace("/eed$/", 'ee', $word);
				}
			},
			"eedly" => function($word) {
				if(strstr(Stemmer::getR1($word), 'eedly')) {
					return preg_replace("/eedly$/", 'ee', $word);
				}
			},
			"ed" => function($word) {
				$rest = substr($word, 0, strlen($word) - 2);
				if(preg_match('/[aeiouy]/', $rest) > 0) {
					$word = $rest;
					$newSuffix = substr($word, -2, 2);
					$endings = array('at', 'bl', 'iz');
					$doubles = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');
					if(in_array($newSuffix, $endings)) {
						$word = $word . 'e';
					} else if(in_array($newSuffix, $doubles)) {
						$word = substr($word, 0, strlen($word) - 1);
					} else if(Stemmer::isShort($word)) {
						$word = $word . 'e';
					}
				}
				return $word; 
			},
			"edly" => function($word) {
				$rest = substr($word, 0, strlen($word) - 4);
				if(preg_match('/[aeiouy]/', $rest) > 0) {
					$word = $rest;
					$newSuffix = substr($word, -2, 2);
					$endings = array('at', 'bl', 'iz');
					$doubles = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');
					if(in_array($newSuffix, $endings)) {
						$word = $word . 'e';
					} else if(in_array($newSuffix, $doubles)) {
						$word = substr($word, 0, strlen($word) - 1);
					} else if(Stemmer::isShort($word)) {
						$word = $word . 'e';
					}
				}
				return $word;
			},
			"ing" => function($word) {
				$rest = substr($word, 0, strlen($word) - 3);
				if(preg_match('/[aeiouy]/', $rest) > 0) {
					$word = $rest;
					$newSuffix = substr($word, -2, 2);
					$endings = array('at', 'bl', 'iz');
					$doubles = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');
					if(in_array($newSuffix, $endings)) {
						$word = $word . 'e';
					} else if(in_array($newSuffix, $doubles)) {
						$word = substr($word, 0, strlen($word) - 1);
					} else if(Stemmer::isShort($word)) {
						$word = $word . 'e';
					}
				}
				return $word;
			},
			"ingly" => function($word) {
				$rest = substr($word, 0, strlen($word) - 5);
				if(preg_match('/[aeiouy]/', $rest) > 0) {
					$word = $rest;
					$newSuffix = substr($word, -2, 2);
					$endings = array('at', 'bl', 'iz');
					$doubles = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');
					if(in_array($newSuffix, $endings)) {
						$word = $word . 'e';
					} else if(in_array($newSuffix, $doubles)) {
						$word = substr($word, 0, strlen($word) - 1);
					} else if(Stemmer::isShort($word)) {
						$word = $word . 'e';
					}
				}
				return $word;
			},
		));
		
		// c:
		$this->currentStem = preg_replace('/([bcdfghjklmnpqrstvwxz])[yY]$/', '$1i', $this->currentStem);
	}
	
	/**
	 * Processes the current stem with the 2nd step
	 * of the Porter algorithm.
	 *
	 * @return void
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	protected function step2() {
		$this->currentStem = $this->performOnLongestSuffix($this->currentStem, array(
			"tional" => function($word) {
				return preg_replace("/tional$/", 'tion', $word);
			},
			"enci" => function($word) {
				return preg_replace("/enci$/", 'ence', $word);
			},
			"anci" => function($word) {
				return preg_replace("/anci$/", 'ance', $word);
			},
			"abli" => function($word) {
				return preg_replace("/abli$/", 'able', $word);
			},
			"entli" => function($word) {
				return preg_replace("/entli$/", 'ent', $word);
			},
			"izer" => function($word) {
				return preg_replace("/izer$/", 'ize', $word);
			},
			"ization" => function($word) {
				return preg_replace("/ization$/", 'ize', $word);
			},
			"ational" => function($word) {
				return preg_replace("/ational$/", 'ate', $word);
			},
			"ation" => function($word) {
				return preg_replace("/ation$/", 'ate', $word);
			},
			"ator" => function($word) {
				return preg_replace("/ator$/", 'ate', $word);
			},
			"alism" => function($word) {
				return preg_replace("/alism$/", 'al', $word);
			},
			"aliti" => function($word) {
				return preg_replace("/aliti$/", 'al', $word);
			},
			"alli" => function($word) {
				return preg_replace("/alli$/", 'al', $word);
			},
			"fulness" => function($word) {
				return preg_replace("/fulness$/", 'ful', $word);
			},
			"ousli" => function($word) {
				return preg_replace("/ousli$/", 'ous', $word);
			},
			"ousness" => function($word) {
				return preg_replace("/ousness$/", 'ous', $word);
			},
			"iveness" => function($word) {
				return preg_replace("/iveness$/", 'ive', $word);
			},
			"iviti" => function($word) {
				return preg_replace("/iviti$/", 'ive', $word);
			},
			"biliti" => function($word) {
				return preg_replace("/biliti$/", 'ble', $word);
			},
			"bli" => function($word) {
				return preg_replace("/bli$/", 'ble', $word);
			},
			"ogi" => function($word) {
				return preg_replace("/logi$/", 'log', $word);
			},
			"fulli" => function($word) {
				return preg_replace("/fulli$/", 'ful', $word);
			},
			"lessli" => function($word) {
				return preg_replace("/lessli$/", 'less', $word);
			},
			"li" => function($word) {
				return preg_replace("/([cdeghkmnrt])li$/", '$1', $word);
			},
		), true);
	}
	
	/**
	 * Processes the current stem with the 3rd step
	 * of the Porter algorithm.
	 *
	 * @return void
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	protected function step3() {
		$this->currentStem = $this->performOnLongestSuffix($this->currentStem, array(
			"tional" => function($word) {
				return preg_replace("/tional$/", 'tion', $word);
			},
			"ational" => function($word) {
				return preg_replace("/ational$/", 'ate', $word);
			},
			"alize" => function($word) {
				return preg_replace("/alize$/", 'al', $word);
			},
			"icate" => function($word) {
				return preg_replace("/icate$/", 'ic', $word);
			},
			"iciti" => function($word) {
				return preg_replace("/iciti$/", 'ic', $word);
			},
			"ical" => function($word) {
				return preg_replace("/ical$/", 'ic', $word);
			},
			"ful" => function($word) {
				return preg_replace("/ful$/", '', $word);
			},
			"ness" => function($word) {
				return preg_replace("/ness$/", '', $word);
			},
			"ative" => function($word) {
				$r2 = Stemmer::getR2($word);
				if(strstr($r2, 'ative') !== false) {
					return preg_replace("/ative$/", '', $word);
				} else {
					return $word;
				}
			},
		), true);
	}
	
	/**
	 * Processes the current stem with the 4th step
	 * of the Porter algorithm.
	 *
	 * @return void
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	protected function step4() {
		$this->currentStem = $this->performOnLongestSuffix($this->currentStem, array(
			"al" => function($word) {
				return preg_replace("/al$/", '', $word);
			},
			"ance" => function($word) {
				return preg_replace("/ance$/", '', $word);
			},
			"ence" => function($word) {
				return preg_replace("/ence$/", '', $word);
			},
			"er" => function($word) {
				return preg_replace("/er$/", '', $word);
			},
			"ic" => function($word) {
				return preg_replace("/ic$/", '', $word);
			},
			"able" => function($word) {
				return preg_replace("/able$/", '', $word);
			},
			"ible" => function($word) {
				return preg_replace("/ible$/", '', $word);
			},
			"ant" => function($word) {
				return preg_replace("/ant$/", '', $word);
			},
			"ement" => function($word) {
				return preg_replace("/ement$/", '', $word);
			},
			"ment" => function($word) {
				return preg_replace("/ment$/", '', $word);
			},
			"ent" => function($word) {
				return preg_replace("/ent$/", '', $word);
			},
			"ism" => function($word) {
				return preg_replace("/ism$/", '', $word);
			},
			"ate" => function($word) {
				return preg_replace("/ate$/", '', $word);
			},
			"iti" => function($word) {
				return preg_replace("/iti$/", '', $word);
			},
			"ous" => function($word) {
				return preg_replace("/ous$/", '', $word);
			},
			"ive" => function($word) {
				return preg_replace("/ive$/", '', $word);
			},
			"ize" => function($word) {
				return preg_replace("/ize$/", '', $word);
			},
			"ion" => function($word) {
				return preg_replace("/[st]ion$/", '', $word);
			}
		), false, true);
	}
	
	/**
	 * Processes the current stem with the 5th step
	 * of the Porter algorithm.
	 *
	 * @return void
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	protected function step5() {
		if(substr($this->currentStem, -1, 1) == 'e') {
			$r2 = Stemmer::getR2($this->currentStem);
			$r1 = Stemmer::getR1($this->currentStem);
			
			// delete if in R2
			if(substr($r2, -1, 1) == 'e') {
				$this->currentStem = preg_replace("/e$/", '', $this->currentStem);
			// ...or in R1 and not preceded by a short syllable
			} else if(substr($r1, -1, 1) == 'e') {
				$sSyllables = Stemmer::getShortSyllables($this->currentStem);
				$matches = 0;
				foreach($sSyllables as $syllable) {
					$matches += preg_match("/{$syllable}e$/", $this->currentStem);
				}
				if($matches === 0) {
					$this->currentStem = preg_replace("/e$/", '', $this->currentStem);
				}
			}
		}
		
		if(substr($this->currentStem, -1, 1) == 'l') {
			$r2 = Stemmer::getR2($this->currentStem);
			if(substr($r2, -1, 1) == 'l') {
				$this->currentStem = preg_replace('/ll$/', 'l', $this->currentStem);
			}
		}
		
		$this->currentStem = strtolower($this->currentStem);
	}
	
	/**
	 * Searches the current stem for the longest suffix and performs the 
	 * supplied closure on it if found. Supplied suffixes should be of the
	 * form:
	 * 
	 * array("suffix" => function($word){ ... })
	 *
	 * @param string $word 
	 * @param array $suffixes 
	 * @param boolean $onlyIfInR1 
	 * @param boolean $onlyIfInR2 
	 * @return string
	 * @author John Anderson
	 */
	protected function performOnLongestSuffix($word, $suffixes = array(), $onlyIfInR1 = false, $onlyIfInR2 = false) {
		$maxMatch = 0;
		foreach($suffixes as $suffix => $replacement) {
			$suffixLength = strlen($suffix);
			if(substr($word, -$suffixLength) === $suffix && $suffixLength > $maxMatch) {
					$matchedSuffix      = $suffix;
					$matchedReplacement = $replacement;
					$maxMatch = $suffixLength;
			}
		}
		
		if(isset($matchedSuffix) && $matchedSuffix) {
			if($onlyIfInR1) {
				if(strstr(Stemmer::getR1($word), $matchedSuffix) !== false) {
					return $matchedReplacement($word);
				} else {
					return $word;
				}
			} else if($onlyIfInR2) {
				if(strstr(Stemmer::getR2($word), $matchedSuffix) !== false) {
					return $matchedReplacement($word);
				} else {
					return $word;
				}
			} else {
				return $matchedReplacement($word);
			}
		}
		return $word;
	}
	
	/**
	 * Returns the R1 region of the supplied word.
	 *
	 * @param string $word 
	 * @return void
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	public static function getR1($word) {
		//Exceptional forms
		if(substr($word, 0, 5) === 'gener') {
			return substr($word, 5);
		} 
		if(substr($word, 0, 6) === 'commun') {
			return substr($word, 6);
		}
		if(substr($word, 0, 5) === 'arsen') {
			return substr($word, 5);
		}
		
		preg_match('/[aeiouy][bcdfghjklmnpqrstvwxYz](.*)$/', $word, $matches);
		if(isset($matches[1])) {
			return $matches[1];
		} else {
			return '';
		}
	}
	
	/**
	 * Returns the R2 region of the supplied word.
	 *
	 * @param string $word 
	 * @return string
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	public static function getR2($word) {
		return Stemmer::getR1(Stemmer::getR1($word));	
	}
	
	/**
	 * Returns an array of the "short" syllables found in the supplied word.
	 *
	 * @param string $word 
	 * @return void
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	public static function getShortSyllables($word) {
		preg_match('/[bcdfghjklmnpqrstvwxYz][aeiouy][bcdfghjklmnpqrstvz]/', $word, $matches);
		// preg_match('/^[aeiouy][bcdfghjklmnpqrstvwxYz]/', $word, $moreMatches);
		$moreMatches = array();
		return array_merge($matches, $moreMatches);
	}
	
	/**
	 * Detects "short" words.
	 *
	 * @param string $word 
	 * @return void
	 * @author John Anderson
	 * @see http://snowball.tartarus.org/algorithms/english/stemmer.html
	 */
	public static function isShort($word) {
		//Should end on a short syllable
		$suffixes = Stemmer::getShortSyllables($word);
		if(!count($suffixes)) {
			return false;
		}
		$matches = false;
		foreach($suffixes as $suffix) {
			if(substr($word, -strlen($suffix)) == $suffix) {
				$matches = true;
			}
		}
		if(!$matches) {
			return false;
		}
		//R1 should also be empty
		return !Stemmer::getR1($word);
	}
}

?>