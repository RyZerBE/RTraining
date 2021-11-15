<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches\item;

use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customItem\CustomItem;
use ryzerbe\core\util\customItem\CustomItemManager;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesGameSession;
use ryzerbe\training\gameserver\minigame\type\clutches\HitQueue;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;

class ClutchesStartItem extends CustomItem {
    public function onInteract(PMMPPlayer $player, Item $item): void{
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
        $stopItem?->giveToPlayer($player, MinigameDefaultSlots::SLOT_OTHER_ITEM);

        $gameEntity = $gameSession->getSettings()->entity;
        $gameEntity?->setNameTag(TextFormat::RED.TextFormat::BOLD."Clutcher\n".TextFormat::AQUA."Ry".TextFormat::WHITE."Z".TextFormat::AQUA."er".TextFormat::WHITE."BE");
    }
}