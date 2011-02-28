<?php

namespace li3_simplesearch\models;

/**
 * Term model.
 *
 * @package default
 * @author John Anderson
 */
class Term extends \lithium\data\Model {
	/**
	 * Finds and ranks search results based on a set of stems from 
	 * a query.
	 *
	 * @param array $terms 
	 * @param int $totalDocuments 
	 * @return array
	 * @author John Anderson
	 */
	public static function findRankedByTerms($terms, $totalDocuments) {
		$conditions = '';
		foreach($terms as $term) {
			$conditions[] = "`term` = '$term'";
		}
		$docsContainingTerms = static::find('count', array(
			'conditions' => array(
				'OR' => $conditions,
			),
		));

		$global = static::calculateGlobalMultiplier($totalDocuments, $docsContainingTerms);

		if($docsContainingTerms > 0) {
			$stringConditions = implode(' OR ', $conditions);
			$sql = "
			SELECT
				Term.`id`,
				`page_id`,
				sum(`base` * `normalization` * $global) as `totalRank`,
				Page.url
			FROM
				`terms` as `Term`
			INNER JOIN
				`Pages` as `Page`
			ON
				`Term`.`page_id` = `Page`.`id`
			WHERE
				$stringConditions
			GROUP BY
				`page_id`
			ORDER BY `totalRank` DESC
			";
			
			$weightedTerms = static::connection()->read($sql, array('type' => 'read', 'model' => static::meta('name')));
		} else {
			$weightedTerms = array();
		}
		return $weightedTerms;
	}

	/**
	 * Calculates the global multiplier component of the "Ranking with 
	 * Vector Spaces" algorithm.
	 *
	 * @param string $total 
	 * @param string $containing 
	 * @return void
	 * @author John Anderson
	 * @see http://forge.mysql.com/wiki/MySQL_Internals_Algorithms#Full-text_Search
	 */
	public static function calculateGlobalMultiplier($total, $containing) {
		return log(($total - $containing) / $containing);
	}
}