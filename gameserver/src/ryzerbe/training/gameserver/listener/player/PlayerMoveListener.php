<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\listener\player;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\Player;
use ryzerbe\training\gameserver\minigame\MinigameManager;

class PlayerMoveListener implements Listener {
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof Player) return;
        $minigame = MinigameManager::getMinigameByPlayer($player);
        if($minigame !== null) {
            $settings = $minigame->getSettings();
            if($player->y <= $settings->deathHeight) {
                $player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_VOID, 100));
            }
        }
    }
}