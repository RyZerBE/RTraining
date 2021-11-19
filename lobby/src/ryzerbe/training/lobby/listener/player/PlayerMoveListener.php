<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\listener\player;

use pocketmine\block\BlockIds;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\training\lobby\gamezone\GameZoneManager;
use function array_rand;

class PlayerMoveListener implements Listener {
    public const MOTION_TYPES = [
        [3.5, 3.5, 0],
        [-3.1, 3.5, 0],
        [0, 3.5, 3],
    ];

    public function onPlayerMove(PlayerMoveEvent $event): void {
        /** @var PMMPPlayer $player */
        $player = $event->getPlayer();
        $level = $player->getLevel();

        if(
            !$player->hasDelay("gamezone_cooldown") &&
            $level->getBlock($player->down())->getId() === BlockIds::SLIME_BLOCK &&
            $level->getBlock($player->down(3))->getId() === BlockIds::GOLD_BLOCK
        ) {
            $type = self::MOTION_TYPES[array_rand(self::MOTION_TYPES)];
            $player->addDelay("gamezone_cooldown", 1);
            $player->teleport(new Vector3(2.5, 116, 23.5));
            $player->setMotion(new Vector3($type[0], $type[1], $type[2]));
            $player->playSound("random.fizz", 100.0, 1.0, [$player]);
        }

        /** @var GameZoneManager $gamezone */
        $gameZone = GameZoneManager::getInstance();
        if($player->y >= GameZoneManager::MIN_Y) {
            if(!$gameZone->isPlayer($player)) {
                $gameZone->addPlayer($player);
                $player->sendActionBarMessage(LanguageProvider::getMessageContainer("game-zone-entered", $player));
            }
        } else {
            if($gameZone->isPlayer($player)) {
                $gameZone->removePlayer($player);
                $player->sendActionBarMessage(LanguageProvider::getMessageContainer("game-zone-left", $player));
            }
        }
    }
}