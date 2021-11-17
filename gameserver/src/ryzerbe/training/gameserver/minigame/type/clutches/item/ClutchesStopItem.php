<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches\item;

use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItem;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesGameSession;
use ryzerbe\training\gameserver\minigame\type\clutches\HitQueue;
use ryzerbe\training\gameserver\session\SessionManager;

class ClutchesStopItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
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

    public function getSlot(): ?int{
        return 4;
    }
}