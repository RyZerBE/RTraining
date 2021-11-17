<?php

namespace ryzerbe\training\gameserver\module\setup\item;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\level\Location;
use pocketmine\level\particle\DustParticle;
use ryzerbe\core\util\customitem\CustomItem;
use ryzerbe\training\gameserver\module\setup\SetupModule;
use ryzerbe\training\gameserver\module\setup\util\SetupUtils;
use function mt_rand;

class SpectatorPositionSetupItem extends CustomItem {
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $item = $event->getItem();
        $arena = SetupModule::getInstance()->getArena();
        $player = $event->getPlayer();
        if(!$this->checkItem($item) || $player->hasItemCooldown($item) || $arena === null) return;
        $player->resetItemCooldown($item, 5);
        $block = $event->getBlock();

        $position = $block->floor()->add(0.5, 0, 0.5);
        $location = new Location($position->x, $position->y, $position->z, SetupUtils::calculateYaw($player, $position), 0, $player->getLevel());

        for ($n = 1; $n <= 20; $n++) {
            $player->getLevel()->addParticle(new DustParticle($position->add(0, $n / 10), mt_rand(), mt_rand(), mt_rand(), mt_rand()));
        }

        $arena->setSpectatorLocation($location);
        $player->sendMessage("§8» §7Set location for spectator.");
    }

}