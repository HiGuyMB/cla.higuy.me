<?php

namespace CLAList;

use CLAList\Entity\Interior;
use CLAList\Entity\Mission;
use CLAList\Entity\Texture;

class EnumModification extends EnumType {
	protected $name = 'modification';
	protected $values = [self::GOLD, self::PLATINUM, self::FUBAR, self::ULTRA, self::PLATINUMQUEST];
	protected static $index = [
		self::GOLD => 0,
		self::PLATINUM => 1,
		self::FUBAR => 1,
		self::ULTRA => 1,
		self::PLATINUMQUEST => 2,
	];

	const GOLD = 'gold';
	const PLATINUM = 'platinum';
	const FUBAR = 'fubar';
	const ULTRA = 'ultra';
	const PLATINUMQUEST = 'platinumquest';

	private static function pickHigher($mod1, $mod2) {
		if (self::$index[$mod1] < self::$index[$mod2]) {
			return $mod2;
		}
		return $mod1;
	}

	public static function guessModification(Mission $mission) {
		//Easy one
		if ($mission->hasField("game") && strcasecmp($mission->getFieldValue("game"), "Custom") !== 0) return $mission->getFieldValue("game");
		if ($mission->hasField("modification")) return $mission->getFieldValue("modification");

		$mod = self::GOLD;

		//Some basic indicators
		if ($mission->hasField("platinumTime")) $mod = self::pickHigher($mod, "platinumquest"); //Added in PQ
		if ($mission->hasField("awesomeTime")) $mod = self::pickHigher($mod, "platinumquest");
		if ($mission->hasField("awesomeScore")) $mod = self::pickHigher($mod, "platinumquest");
		if ($mission->hasField("ultimateTime")) $mod = self::pickHigher($mod, "platinum");
		if ($mission->hasField("ultimateScore")) $mod = self::pickHigher($mod, "platinum");
		if ($mission->hasField("awesomeScore[0]")) $mod = self::pickHigher($mod, "platinumquest");
		if ($mission->hasField("awesomeScore[1]")) $mod = self::pickHigher($mod, "platinumquest");
		if ($mission->hasField("score[0]")) $mod = self::pickHigher($mod, "platinum");
		if ($mission->hasField("score[1]")) $mod = self::pickHigher($mod, "platinum");
		if ($mission->hasField("platinumScore[0]")) $mod = self::pickHigher($mod, "platinum");
		if ($mission->hasField("platinumScore[1]")) $mod = self::pickHigher($mod, "platinum");
		if ($mission->hasField("ultimateScore[0]")) $mod = self::pickHigher($mod, "platinum");
		if ($mission->hasField("ultimateScore[1]")) $mod = self::pickHigher($mod, "platinum");
		if ($mission->getEasterEgg()) $mod = self::pickHigher($mod, "platinum");

		//Check interiors
		foreach ($mission->getInteriors() as $interior) {
			/* @var Interior $interior */
			$file = $interior->getGamePath();
			if (stripos($file, "pq_") !== false) $mod = self::pickHigher($mod, "platinum");
			if (stripos($file, "interiors_pq") !== false) $mod = self::pickHigher($mod, "platinumquest");
			if (stripos($file, "mbp_") !== false) $mod = self::pickHigher($mod, "platinum");
			if (stripos($file, "interiors_mbp") !== false) $mod = self::pickHigher($mod, "platinum");
			if (stripos($file, "fubargame") !== false) $mod = self::pickHigher($mod, "fubar");

			$textures = $interior->getTextures();
			foreach ($textures as $texture) {
				/* @var Texture $texture */
				if (stripos($texture->getGamePath(), "pq_") !== false) $mod = self::pickHigher($mod, "platinumquest");
				if (stripos($texture->getGamePath(), "mbp_") !== false) $mod = self::pickHigher($mod, "platinum");
				if (stripos($texture->getGamePath(), "mbu_") !== false) $mod = self::pickHigher($mod, "platinum");
			}
		}

		return $mod;
	}

}
