<?php

namespace ryzerbe\training\gameserver\listener\entity;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\session\SessionManager;
use function method_exists;

class EntityDamageListener implements Listener {
    /**
     * @priority LOW
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        $minigame = MinigameManager::getMinigameByLevel($entity->getLevelNonNull());
        if($minigame !== null) {
            if(!$entity instanceof Player) return;

            $minigame = MinigameManager::getMinigameByPlayer($entity);
            if($minigame === null){
                $event->setCancelled();
                return;
            }
            $settings = $minigame->getSettings();
            $session = $minigame->getSessionManager()->getSessionByPlayer($entity);
            $gameSession = $session->getGameSession();
            if(method_exists($gameSession, "isRunning")) {
                if(!$gameSession->isRunning()) {
                    $event->setCancelled();
                    return;
                }
            }

            if($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if($damager instanceof Player) {
                    $damagerSession = SessionManager::getInstance()->getSessionOfPlayer($damager);
                    foreach($damagerSession->getTeams() as $team) {
                        if($team->isPlayer($damager) && $team->isPlayer($entity)) {
                            $event->setCancelled();
                            return;
                        }
                    }
                    if(!$settings->pvp){
                        $event->setCancelled();
                        return;
                    }
                }

                $entity->setLastAttackedEntity($damager);
                $entityName = $entity->getName();
                AsyncExecutor::submitClosureTask(20 * 15, function(int $currentTick) use ($entityName): void{
                    $player = Server::getInstance()->getPlayerExact($entityName);
                    if($player === null) return;

                    $player->setLastAttackedEntity(null);
                });
            }
            if(!$settings->damage) $event->setCancelled();
            if(!$entity->isSurvival(true)) $event->setCancelled();

            if($event->isCancelled()) return;
            if($event->getFinalDamage() >= $entity->getHealth()) {
                $event->setCancelled();

                $ev = new PlayerDeathEvent($entity, $entity->getDrops(), "DEFAULT", $entity->getXpDropAmount());
                $ev->call();

                if(!$ev->getKeepInventory()){
                    foreach($ev->getDrops() as $item){
                        $entity->getLevelNonNull()->dropItem($entity->getLocation(), $item);
                    }

                    $entity->getInventory()?->setHeldItemIndex(0);
                    $entity->getInventory()?->clearAll();
                    $entity->getArmorInventory()?->clearAll();
                    $entity->getOffHandInventory()?->clearAll();
                }

                $entity->getLevelNonNull()->dropExperience($entity->getLocation(), $ev->getXpDropAmount());
                $entity->setXpLevel(0);
                $entity->setXpProgress(0);


                AsyncExecutor::submitClosureTask(1, function(int $currentTick) use ($entity): void{
                    if(!$entity->isConnected()) return;

                    $entity->setSprinting(false);
                    $entity->setSneaking(false);
                    $entity->setFlying(false);

                    $entity->extinguish();
                    $entity->setAirSupplyTicks($entity->getMaxAirSupplyTicks());
                    $entity->deadTicks = 0;
                    $entity->noDamageTicks = 60;

                    $entity->removeAllEffects();
                    $entity->setHealth($entity->getMaxHealth());

                    foreach($entity->getAttributeMap()->getAll() as $attr){
                        $attr->resetToDefault();
                    }

                    $ev = new PlayerRespawnEvent($entity, $entity->getLevel()->getSafeSpawn());
                    $ev->call();
                });
            }
        } else {
            $event->setCancelled();
        }
    }
}