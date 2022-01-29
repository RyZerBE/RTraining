<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesGameSession;
use ryzerbe\training\gameserver\minigame\type\clutches\form\ClutchesSettingForm;
use ryzerbe\training\gameserver\session\SessionManager;

class ClutchesConfigurationItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 20);

        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return;
        ClutchesSettingForm::open($player);
        $gameSession->setRunning(false);
    }

    public function getSlot(): ?int{
        return 8;
    }
}