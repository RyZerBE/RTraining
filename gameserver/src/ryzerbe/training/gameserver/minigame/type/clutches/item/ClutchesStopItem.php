<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches\item;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesGameSession;
use ryzerbe\training\gameserver\minigame\type\clutches\HitQueue;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\customItem\TrainingItem;

class ClutchesStopItem extends TrainingItem {

    public function getSlot(): int{
        return 4;
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if(!$this->checkItem($item)) return;
        if($player->hasItemCooldown($item)) return;
        $player->resetItemCooldown($item, 20);

        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        if($session === null) return;

        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return;
        $gameSession->reset(ClutchesStartItem::class);

        HitQueue::removeQueue($player->getName());

        $gameEntity = $gameSession->getSettings()->entity;
        $gameEntity?->setNameTag(TextFormat::YELLOW."Click to change your settings");
    }
}