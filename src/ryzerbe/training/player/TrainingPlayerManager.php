<?php

namespace ryzerbe\training\player;

use pocketmine\Player;

class TrainingPlayerManager {

    /** @var TrainingPlayer[]  */
    private static array $players = [];

    /**
     * @return TrainingPlayer[]
     */
    public static function getPlayers(): array{
        return self::$players;
    }

    /**
     * @param TrainingPlayer $player
     */
    public static function addPlayer(TrainingPlayer $player): void{
        self::$players[$player->getPlayer()->getName()] = $player;
    }

    /**
     * @param Player|string $player
     */
    public static function removePlayer(Player|string $player): void{
        if($player instanceof Player)
            $player = $player->getName();

        unset(self::$players[$player]);
    }

    /**
     * @param Player|string $player
     * @return TrainingPlayer|null
     */
    public static function getPlayer(Player|string $player): ?TrainingPlayer{
        if($player instanceof Player)
            $player = $player->getName();

        return self::$players[$player] ?? null;
    }
}