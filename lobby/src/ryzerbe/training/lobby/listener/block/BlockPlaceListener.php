<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\listener\block;

use pocketmine\block\BlockIds;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use ryzerbe\training\lobby\gamezone\GameZoneManager;

class BlockPlaceListener implements Listener {
    public function onBlockPlace(BlockPlaceEvent $event): void{
        $player = $event->getPlayer();
        if($player->isCreative(true)) return;

        /** @var GameZoneManager $gameZone */
        $gameZone = GameZoneManager::getInstance();
        $block = $event->getBlock();
        if($gameZone->isPlayer($player)) {
            if($block->y <= GameZoneManager::MIN_Y || $block->y >= GameZoneManager::MAX_Y) {
                $event->setCancelled();
                return;
            }
            if($block->getId() === BlockIds::SANDSTONE) {
                $player->getInventory()->setItemInHand($event->getItem()->setCount(64));
            }
            $gameZone->scheduleBlock($event->getBlockReplaced());
            return;
        }
        $event->setCancelled();
    }
}