<?php

namespace ryzerbe\training\lobby\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerToggleSneakEvent;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\training\lobby\item\TrainingItemManager;
use ryzerbe\training\lobby\kit\KitManager;

class PlayerSneakListener implements Listener {

    /**
     * @param PlayerToggleSneakEvent $event
     */
    public function onSneak(PlayerToggleSneakEvent $event){
        /** @var PMMPPlayer $player */
        $player = $event->getPlayer();
        $kitName = KitManager::getInstance()->sort[$player->getName()] ?? null;
        if($kitName === null) return;

        KitManager::getInstance()->savePlayerKitSort($player, $kitName, $player->getInventory()->getContents());
        $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        unset(KitManager::getInstance()->sort[$player->getName()]);
        $player->getInventory()->clearAll();

        foreach(TrainingItemManager::getInstance()->getItems() as $trainingItem) {
            $trainingItem->giveToPlayer($player);
        }
        $player->getInventory()->setHeldItemIndex(4);
        $player->setImmobile(false);
        $player->removeAllEffects();
    }
}