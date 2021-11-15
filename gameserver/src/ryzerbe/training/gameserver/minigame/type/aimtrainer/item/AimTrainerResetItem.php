<?php

namespace ryzerbe\training\gameserver\minigame\type\aimtrainer\item;

use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customItem\CustomItem;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\AimTrainerGameSession;
use ryzerbe\training\gameserver\session\SessionManager;

class AimTrainerResetItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
        $player->resetItemCooldown($item, 20);

        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        if($session === null) return;
        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof AimTrainerGameSession) return;
        $aimTrainerEntity = $gameSession->getEntity();
        if($aimTrainerEntity === null || $aimTrainerEntity->isClosed()) return;

        $aimTrainerEntity->teleport($gameSession->getEntityPosition());
        $gameSession->resetHitCount(true);
        $gameSession->sendScoreboard();
        $player->playSound("random.levelup");
    }
}