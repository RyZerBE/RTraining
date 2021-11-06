<?php

namespace ryzerbe\training\gameserver\util;

use pocketmine\Player;
use function time;

class WaitingQueue {
    public static array $queue = [];

    public static function getQueue(): array{
        return self::$queue;
    }

    public static function addPlayer(Player|string $player, int $time = 15){
        if($player instanceof Player) $player = $player->getName();
        self::$queue[$player] = time() + $time;
    }

    public static function removePlayer(Player|string $player){
        if($player instanceof Player) $player = $player->getName();
        unset(self::$queue[$player]);
    }
}