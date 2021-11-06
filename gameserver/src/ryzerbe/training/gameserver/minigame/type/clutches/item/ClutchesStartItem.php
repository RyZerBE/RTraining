<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches\item;

use pocketmine\block\BlockIds;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesGameSession;
use ryzerbe\training\gameserver\minigame\type\clutches\HitQueue;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\customItem\CustomItemManager;
use ryzerbe\training\gameserver\util\customItem\TrainingItem;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;

class ClutchesStartItem extends TrainingItem {
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if(!$this->checkItem($item)) return;
        if($player->hasItemCooldown($item)) return;
        $player->resetItemCooldown($item, 20);

        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        if($session === null) return;

        /** @var ClutchesGameSession $gameSession */
        $gameSession = $session->getGameSession();
        $inventory = $player->getInventory();
        $inventory->clearAll();

        HitQueue::addQueue($gameSession);
        $player->setImmobile(false);

        $inventory->setItem(MinigameDefaultSlots::SLOT_BLOCK_ITEM, Item::get(BlockIds::SANDSTONE, 0, 64));

        $stopItem = CustomItemManager::getInstance()->getCustomItemByClass(ClutchesStopItem::class);
        $stopItem?->giveItem($player, MinigameDefaultSlots::SLOT_OTHER_ITEM);

        $gameEntity = $gameSession->getSettings()->entity;
        $gameEntity?->setNameTag(TextFormat::RED.TextFormat::BOLD."Clutcher\n".TextFormat::AQUA."Ry".TextFormat::WHITE."Z".TextFormat::AQUA."er".TextFormat::WHITE."BE");
    }
}