<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesGameSession;
use ryzerbe\training\gameserver\session\SessionManager;

class ClutchesStartItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 20);

        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return;
        $gameSession->setRunning(true);
        $gameSession->reset(false);
    }
}