<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\hitblockclutch;

use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\customitem\CustomItemManager;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\Minigame;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\entity\HitBlockClutchEntity;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\generator\HitBlockClutchGenerator;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\item\HitBlockClutchMinigameConfigurationItem;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use function array_filter;
use function count;
use function in_array;
use function mt_rand;
use function number_format;

class HitBlockClutchMinigame extends Minigame {
    private Level $level;

    public function __construct(){
        parent::__construct();
        CustomItemManager::getInstance()->registerAll([
            new HitBlockClutchMinigameConfigurationItem(Item::get(ItemIds::BOOK)->setCustomName(TextFormat::RED."Settings"), 8),
        ]);

        GeneratorManager::addGenerator(HitBlockClutchGenerator::class, "hitblockclutch", true);
        Server::getInstance()->generateLevel("HitBlockClutch", mt_rand(), HitBlockClutchGenerator::class);
        $this->level = Server::getInstance()->getLevelByName("HitBlockClutch");
        $this->level->setTime(6000);
        $this->level->stopTime();
    }

    public function getName(): string{
        return "Hit Block Clutch";
    }

    public function initSettings(): void{
        $this->settings = new HitBlockClutchSettings();
    }

    public function getLevel(): Level{
        return $this->level;
    }

    public function constructGameSession(Session $session): GameSession{
        $usedIds = [];
        foreach($this->getSessionManager()->getSessions() as $Session) {
            $gameSession = $Session->getGameSession();
            if($gameSession instanceof HitBlockClutchGameSession) $usedIds[] = $gameSession->getPlatformId();
        }
        $id = 0;
        while(in_array($id, $usedIds)) $id++;
        return new HitBlockClutchGameSession($session, $this->getLevel(), $id);
    }

    public function onLoad(Session $session): void{
        /** @var HitBlockClutchGameSession $gameSession */
        $gameSession = $session->getGameSession();

        /** @var PMMPPlayer $player */
        $player = $session->getPlayer();
        if($player === null) return;

        $player->teleport($gameSession->getSpawn());
        $player->setImmobile();
        AsyncExecutor::submitClosureTask(60, function(int $tick) use ($player, $gameSession): void {
            if(!$player->isConnected()) return;
            $player->setGamemode(0);
            $player->setImmobile(false);
            $gameSession->getMap()->generate($gameSession);
            $gameSession->resetGame();
        });
    }

    public function onUnload(Session $session): void{
        /** @var HitBlockClutchGameSession $gameSession */
        $gameSession = $session->getGameSession();
        $gameSession->resetAllBlocks();
    }

    public function onUpdate(Session $session, int $currentTick): bool{
        /** @var HitBlockClutchGameSession $gameSession */
        $gameSession = $session->getGameSession();
        if(!$gameSession->isTimerRunning()) return false;
        $player = $session->getPlayer();
        if($player === null) return false;
        $player->sendActionBarMessage("§l§6" . number_format($gameSession->getTimer(), 2) . " Seconds..");
        return true;
    }

    public function onDamage(EntityDamageEvent $event): void{
        if($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $entity = $event->getEntity();
            if(!$entity instanceof Player) return;
            $gameSession = SessionManager::getInstance()->getSessionOfPlayer($entity)?->getGameSession();
            if(!$gameSession instanceof HitBlockClutchGameSession) return;
            $event->setCancelled();

            $entity->teleport($gameSession->getSpawn());

            if($gameSession->isTimerRunning()){
                $gameSession->stopTimer();
                $gameSession->resetGame();
            }
        }
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof HitBlockClutchGameSession) return;
        if(!$player->isOnGround()) return;

        $level = $player->getLevel();
        $block = $level->getBlock($player);
        $blockSideDown = $block->getSide(Vector3::SIDE_DOWN);
        switch($block->getId()) {
            case BlockIds::LIGHT_WEIGHTED_PRESSURE_PLATE: {
                switch($blockSideDown->getId()) {
                    case BlockIds::REDSTONE_BLOCK: {
                        if($gameSession->isTimerRunning()) {
                            $id = $gameSession->getPlatformId();
                            if(count(array_filter($player->getLevel()->getEntities(), function(Entity $entity) use ($id): bool {
                                return $entity instanceof HitBlockClutchEntity && $entity->getGameSession()->getPlatformId() === $id;
                            })) > 0) return;

                            $gameSession->stopTimer();
                            $gameSession->updateScore();
                            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
                            $gameSession->resetGame();
                        }
                        return;
                    }
                    default: {
                        if(!$gameSession->isTimerRunning()) {
                            $gameSession->startTimer();
                            $this->scheduleUpdate($gameSession->getSession());

                            $player->getInventory()->setItem(MinigameDefaultSlots::SLOT_BLOCK_ITEM, Item::get(BlockIds::SANDSTONE, 0, 64));
                        }
                    }
                }
                return;
            }
        }

        switch($blockSideDown->getId()) {
            case BlockIds::EMERALD_BLOCK: {
                $module = $gameSession->getModule();
                $id = $gameSession->getPlatformId();
                if($module !== 1 && count(array_filter($player->getLevel()->getEntities(), function(Entity $entity) use ($module, $id): bool {
                    return $entity instanceof HitBlockClutchEntity && $entity->getGameSession()->getPlatformId() === $id && $entity->getModule() === $module;
                })) > 0) return;
                $vector3 = $player->floor();
                while($level->getBlock($vector3)->getId() !== BlockIds::IRON_BLOCK) {
                    if(!$level->isInWorld(0, ++$vector3->y, 0)) return;
                }
                $player->teleport($vector3->add(0.5, 1, 0.5));
                $player->playSound("mob.shulker.teleport", 5.0, 1.0, [$player]);

                $module = $gameSession->getMap()->getModuleByPosition($blockSideDown);
                if($module !== null) $gameSession->setModule($module);
                return;
            }
        }
    }

    /**
     * @priority HIGH
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof HitBlockClutchGameSession) return;
        $block = $event->getBlock();

        $event->setCancelled();
        if(!$gameSession->isTimerRunning()) return;

        $module = $gameSession->getModule();
        $id = $gameSession->getPlatformId();
        if(count(array_filter($player->getLevel()->getEntities(), function(Entity $entity) use ($module, $id): bool {
            return $entity instanceof HitBlockClutchEntity && $entity->getGameSession()->getPlatformId() === $id && $entity->getModule() === $module;
        })) <= 0) {
            foreach($block->getAllSides() as $side) {
                if($side->getId() !== BlockIds::CONCRETE) continue;
                $event->setCancelled(false);
                break;
            }
        }

        if(!$event->isCancelled()) {
            $gameSession->addBlock($block, "player");
        }
    }
}