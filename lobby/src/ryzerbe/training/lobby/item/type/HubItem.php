<?php

namespace ryzerbe\training\lobby\item\type;

use BauboLP\Cloud\CloudBridge;
use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;

class HubItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 20);

        CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "hub");
    }
}