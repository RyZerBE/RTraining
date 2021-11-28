<?php

namespace ryzerbe\training\gameserver\listener\block;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\session\SessionManager;
use function method_exists;

class BlockPlaceListener implements Listener {

    /**
     * @param BlockPlaceEvent $event
     * @priority LOW
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $minigame = MinigameManager::getMinigameByPlayer($player);
        if($minigame === null) {
            if($player->isCreative()) return;
            $event->setCancelled();
            return;
        }
        $event->setCancelled(!$minigame->getSettings()->canPlace);
        if(!$event->isCancelled()) {
            $session = SessionManager::getInstance()->getSessionOfPlayer($player);
            if($session === null) return;
            $gameSession = $session->getGameSession();
            if(
                $gameSession === null ||
                !method_exists($gameSession, "addBlock")
            ) return;
            $gameSession->addBlock($event->getBlock());
        }
    }
}