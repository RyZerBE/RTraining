<?php

namespace ryzerbe\training\gameserver\listener\player;

use pocketmine\block\BlockIds;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use ryzerbe\core\player\data\LoginPlayerData;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\minigame\type\kitpvp\KitPvPMinigame;
use function in_array;

class PlayerInteractListener implements Listener {

    /**
     * @param PlayerInteractEvent $event
     * @priority LOW
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof Player) return;
        $minigame = MinigameManager::getMinigameByPlayer($player);
        if($minigame === null) {
            if($player->isCreative()) return;
            $event->setCancelled();
            return;
        }
        if($player->isSpectator()){
            $event->setCancelled();
            return;
        }
        $action = $event->getAction();
        $settings = $minigame->getSettings();
        $cancel = !$settings->canInteract;
        switch($action) {
            case PlayerInteractEvent::RIGHT_CLICK_BLOCK: {
                if($settings->canPlace) break;
            }
            default: {
                $event->setCancelled($cancel);
            }
        }

        if($event->isCancelled()) return;
        $item = $event->getItem();

        if($item->getId() === ItemIds::MUSHROOM_STEW && $minigame instanceof KitPvPMinigame) {
            if((int)$player->getHealth() === $player->getMaxHealth()) return;
            $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($player);
            $player->setHealth($player->getHealth() + 4.5);
            if($rbePlayer === null) {
                $player->getInventory()->setItemInHand(Item::get(ItemIds::BOWL));
            }else {
                $os = [LoginPlayerData::TOUCH, LoginPlayerData::CONTROLLER];
                $player->getInventory()->setItemInHand(in_array($rbePlayer->getLoginPlayerData()->getCurrentInputMode(), $os) ? Item::get(BlockIds::AIR) : Item::get(ItemIds::BOWL));
            }
            $player->playSound("random.eat", 2.0, 1.0, [$player]);
        }
    }
}