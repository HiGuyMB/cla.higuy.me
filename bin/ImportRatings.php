<?php

chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\Model\Entity\Field;
use CLAList\Model\Entity\Mission;
use CLAList\Model\Entity\Rating;

define('CLA_RATING_WEIGHT', 10);

$found = 0;
$nfound = 0;

//SetQueryLogging(true);

if (($ratings_file = fopen("bin/ratings.csv", "r")) !== false) {
	fgetcsv($ratings_file); //Strip header
	while (($row = fgetcsv($ratings_file)) !== false) {
		$name = $row[0];
		$mis = $row[1];
		$diff = $row[2];
		$value = $row[3];

		$mission = Mission::find(["baseName" => $mis], [], false);
		if ($mission === null) {
			//Try by name?
			$field = Field::find(["name" => "name", "value" => $name], [], false);
			if ($field !== null) {
				/* @var Field $field */
				$mission = $field->getMission();
			} else {
				print("Couldn't find $mis\n");
			}
		}

		if ($mission !== null) {
			/* @var Mission $mission */
			$found++;

			if ($value > 0) {
				$rating = Rating::find(["user" => "CLA", "mission" => $mission],
					["CLA", $mission, $value, CLA_RATING_WEIGHT]);
				/* @var Rating $rating */
				$rating->setValue($value);
				$rating->setWeight(CLA_RATING_WEIGHT);
			}

			if ($diff > 0) {
				$mission->setFieldValue("_difficulty", $diff);
			}
		} else {
			$nfound++;
		}
	}
}
GetEntityManager()->flush();

echo("Found $found and didnt find $nfound\n");
