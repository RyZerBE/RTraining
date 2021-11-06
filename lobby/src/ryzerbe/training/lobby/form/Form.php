<?php

namespace ryzerbe\training\lobby\form;

use pocketmine\Player;

abstract class Form {

    abstract public static function open(Player $player, array $extraData = []): void;
}