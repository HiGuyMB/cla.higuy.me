<?php
namespace CLAList;

use CLAList\Entity\Interior;
use CLAList\Entity\Mission;

class EnumGameType extends EnumType {
	protected $name = 'game_type';
	protected $values = [self::SINGLE_PLAYER, self::MULTIPLAYER];

	const SINGLE_PLAYER = 'Single Player';
	const MULTIPLAYER = 'Multiplayer';

	public static function guessGameType(Mission $mission) {
		//Easy check
		if (stripos($mission->getGamePath(), "multiplayer/") !== false) return EnumGameType::MULTIPLAYER;

		//See if it has the telltale MP mission stuff
		if (   $mission->hasField("score[0]") || $mission->hasField("score0")
			|| $mission->hasField("score[1]") || $mission->hasField("score1")
			|| $mission->hasField("goldScore[0]") || $mission->hasField("goldScore0")
			|| $mission->hasField("goldScore[1]") || $mission->hasField("goldScore1")
			|| $mission->hasField("platinumScore[0]") || $mission->hasField("platinumScore0")
			|| $mission->hasField("platinumScore[1]") || $mission->hasField("platinumScore1")
			|| $mission->hasField("ultimateScore[0]") || $mission->hasField("ultimateScore0")
			|| $mission->hasField("ultimateScore[1]") || $mission->hasField("ultimateScore1")
			|| $mission->hasField("awesomeScore[0]") || $mission->hasField("awesomeScore0")
			|| $mission->hasField("awesomeScore[1]") || $mission->hasField("awesomeScore1")
		)
			return EnumGameType::MULTIPLAYER;

		//Check interiors
		foreach ($mission->getInteriors() as $interior) {
			/* @var Interior $interior */
			$file = $interior->getGamePath();
			if (stripos($file, "/multiplayer/interiors/") !== false) return EnumGameType::MULTIPLAYER;
		}

		return EnumGameType::SINGLE_PLAYER;
	}
}

