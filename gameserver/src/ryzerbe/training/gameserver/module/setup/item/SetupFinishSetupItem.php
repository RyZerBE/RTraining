<?php

namespace ryzerbe\training\gameserver\module\setup\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customItem\CustomItem;
use ryzerbe\training\gameserver\module\setup\SetupModule;

class SetupFinishSetupItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 20);
        $arena = SetupModule::getInstance()->getArena();

        $arena->save();
        $player->sendMessage("§8» §7Arena successfully saved. Good job!");
        SetupModule::getInstance()->setArena(null);
        $player->sendMessage("§8» §7SetupArena removed.");

        $player->getInventory()->clearAll();
    }
}