<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\listener\entity;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\Server;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\training\lobby\gamezone\GameZoneManager;
use function in_array;

class EntityDamageListener implements Listener {
    public function onEntityDamage(EntityDamageEvent $event): void{
        $player = $event->getEntity();
        if(!$player instanceof PMMPPlayer) return;
        $cause = $event->getCause();

        /** @var GameZoneManager $gamezone */
        $gameZone = GameZoneManager::getInstance();
        if($gameZone->isPlayer($player)) {
            if(in_array($cause, [
                EntityDamageEvent::CAUSE_CONTACT, EntityDamageEvent::CAUSE_FALL
            ])) {
                $event->setCancelled();
                return;
            }
            $event->setCancelled(false);
            if($event->getFinalDamage() >= $player->getHealth()) {
                GameZoneManager::getInstance()->removePlayer($player);
                $player->teleport(new Vector3(2.5, 115, 18.5), 0, 0);
                $event->setCancelled();

                if($event instanceof EntityDamageByEntityEvent) {
                    $damager = $event->getDamager();
                    if($damager instanceof PMMPPlayer) {
                        $damager->playSound("random.orb", 5.0, 1.0, [$damager]);
                        $gameZone->resetGameZoneItems($damager);
                    }
                }
            }
        } else {
            if($player->isCreative()) return;
            $event->setCancelled();
            if(in_array($cause, [
                EntityDamageEvent::CAUSE_VOID, EntityDamageEvent::CAUSE_SUFFOCATION
            ])) {
                $player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn()->add(0.5, 1, 0.5), 180, 0);
            }
        }
    }
}