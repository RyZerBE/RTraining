<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\game\spectate;

use pocketmine\Player;
use pocketmine\Server;

class SpectateManager {
    protected static array $spectating = [];

    public static function spectate(Player $target, Player $spectator): void {
        self::$spectating[$target->getName()][] = $spectator->getName();
    }

    public static function remove(Player $player): void {
        unset(self::$spectating[$player->getName()]);
    }

    /**
     * @return Player[]
     */
    public static function getSpectators(Player $player): array {
        $spectators = [];
        foreach((self::$spectating[$player->getName()] ?? []) as $key => $value) {
            $spectator = Server::getInstance()->getPlayerExact($value);
            if($spectator === null){
                unset(self::$spectating[$player->getName()][$key]);
                continue;
            }
            $spectators[] = $spectator;
        }
        return $spectators;
    }
}