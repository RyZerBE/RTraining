<?php

namespace ryzerbe\training\listener\block;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use ryzerbe\training\minigame\MinigameManager;
use ryzerbe\training\session\SessionManager;
use function method_exists;

class BlockBreakListener implements Listener {

    /**
     * @param BlockBreakEvent $event
     * @priority LOW
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $minigame = MinigameManager::getMinigameByPlayer($player);
        if($minigame === null) {
            $event->setCancelled();
            return;
        }
        $event->setCancelled(!$minigame->getSettings()->canBreak);
        if($minigame->getSettings()->onlyPlacedBreak) {
            $session = SessionManager::getInstance()->getSessionOfPlayer($player);
            if($session === null) return;
            $gameSession = $session->getGameSession();
            if(
                $gameSession === null ||
                !method_exists($gameSession, "isBlock") ||
                !method_exists($gameSession, "removeBlock")
            ) return;

            if($gameSession->isBlock($block)) {
                $gameSession->removeBlock($block);
            }else {
                $event->setCancelled();
            }
        }
    }
}