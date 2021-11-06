<?php

namespace ryzerbe\training\listener\entity;

use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;
use ryzerbe\training\minigame\MinigameManager;
use function method_exists;

class EntityExplodeListener implements Listener {
    public function onEntityExplode(EntityExplodeEvent $event): void{
        $level = $event->getEntity()->getLevel();
        $minigame = MinigameManager::getMinigameByLevel($level);
        if($minigame === null) {
            $event->setBlockList([]);
            return;
        }
        $gameSession = null;
        foreach($minigame->getSessionManager()->getSessions() as $session) {
            if($session->getGameSession()->getLevel()->getFolderName() === $level->getFolderName()) {
                $gameSession = $session->getGameSession();
            }
        }

        if(!method_exists($gameSession, "isBlock")) {
            $event->setBlockList([]);
            return;
        }

        $newList = [];
        foreach($event->getBlockList() as $block) {
            if($gameSession->isBlock($block)) $newList[] = $block;
        }
        $event->setBlockList($newList);
    }
}