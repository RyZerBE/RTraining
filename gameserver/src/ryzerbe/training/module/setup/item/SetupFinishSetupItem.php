<?php

namespace ryzerbe\training\module\setup\item;

use pocketmine\event\player\PlayerInteractEvent;
use ryzerbe\training\module\setup\SetupModule;
use ryzerbe\training\util\customItem\CustomItem;

class SetupFinishSetupItem extends CustomItem {
    public function onInteract(PlayerInteractEvent $event): void{
        $player = $event->getPlayer();
        $item = $event->getItem();
        $arena = SetupModule::getInstance()->getArena();
        if(!$this->checkItem($item) || $player->hasItemCooldown($item) || $arena === null) return;
        $player->resetItemCooldown($item, 5);

        $arena->save();
        $player->sendMessage("§8» §7Arena successfully saved. Good job!");
        SetupModule::getInstance()->setArena(null);
        $player->sendMessage("§8» §7SetupArena removed.");

        $player->getInventory()->clearAll();
    }
}