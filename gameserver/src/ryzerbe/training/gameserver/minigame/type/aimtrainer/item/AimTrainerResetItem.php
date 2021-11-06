<?php

namespace ryzerbe\training\gameserver\minigame\type\aimtrainer\item;

use pocketmine\event\player\PlayerInteractEvent;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\AimTrainerGameSession;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\customItem\TrainingItem;

class AimTrainerResetItem extends TrainingItem {
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if(!$this->checkItem($item)) return;
        if($player->hasItemCooldown($item)) return;
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