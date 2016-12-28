<?php
namespace CLAList;

class EnumGameType extends EnumType {
	protected $name = 'game_type';
	protected $values = ['Single Player', 'Multiplayer'];

	const SINGLE_PLAYER = 'Single Player';
	const MULTIPLAYER = 'Multiplayer';
}

