<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches;

use ryzerbe\training\gameserver\game\GameSession;
use function microtime;
use function uniqid;

class HitQueue {
    public static array $queue = [];

    public static function getQueue(): array{
        return self::$queue;
    }

    /**
     * @param ClutchesGameSession $session
     */
    public static function addQueue(GameSession $session){
        $player = $session->getSession()->getPlayer();
        if($player === null) return;

        $hit = 1;
        $time = microtime(true) + $session->getSettings()->seconds;
        $hitCount = $session->getSettings()->hit;
        while($hitCount >= $hit) {
            self::$queue[$player->getName()][uniqid()] = $time + ($hit * 0.5);
            $hit++;
        }
    }

    public static function removeQueue(string $key, string $id = null){
        if($id === null) unset(self::$queue[$key]);
        else unset(self::$queue[$key][$id]);
    }
}