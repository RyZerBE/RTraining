<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\speedclutch;

use pocketmine\block\BlockIds;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\customitem\CustomItemManager;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\Minigame;
use ryzerbe\training\gameserver\minigame\type\speedclutch\generator\SpeedClutchGenerator;
use ryzerbe\training\gameserver\minigame\type\speedclutch\item\SpeedClutchMinigameConfigurationItem;
use ryzerbe\training\gameserver\minigame\type\speedclutch\item\SpeedClutchMinigameResetItem;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use function in_array;
use function mt_rand;
use function number_format;

class SpeedClutchMinigame extends Minigame {
    private Level $level;

    public function __construct(){
        parent::__construct();
        CustomItemManager::getInstance()->registerAll([
            new SpeedClutchMinigameConfigurationItem(Item::get(ItemIds::BOOK)->setCustomName(TextFormat::RED."Settings"), 8),
            new SpeedClutchMinigameResetItem(Item::get(ItemIds::RECORD_MELLOHI)->setCustomName(TextFormat::RED."Reset"), 5),
        ]);

        GeneratorManager::addGenerator(SpeedClutchGenerator::class, "speedclutch", true);
        Server::getInstance()->generateLevel("SpeedClutch", mt_rand(), SpeedClutchGenerator::class);
        $this->level = Server::getInstance()->getLevelByName("SpeedClutch");
        $this->level->setTime(6000);
        $this->level->stopTime();
    }

    public function getName(): string{
        return "Speed Clutch";
    }

    public function initSettings(): void{
        $this->settings = new SpeedClutchSettings();
    }

    public function getLevel(): Level{
        return $this->level;
    }

    public function constructGameSession(Session $session): GameSession{
        $usedIds = [];
        foreach($this->getSessionManager()->getSessions() as $Session) {
            $gameSession = $Session->getGameSession();
            if($gameSession instanceof SpeedClutchGameSession) $usedIds[] = $gameSession->getPlatformId();
        }
        $id = 0;
        while(in_array($id, $usedIds)) $id++;
        return new SpeedClutchGameSession($session, $this->getLevel(), $id);
    }

    public function onLoad(Session $session): void{
        /** @var SpeedClutchGameSession $gameSession */
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
        /** @var SpeedClutchGameSession $gameSession */
        $gameSession = $session->getGameSession();
        $gameSession->resetAllBlocks();
    }

    public function onUpdate(Session $session, int $currentTick): bool{
        /** @var SpeedClutchGameSession $gameSession */
        $gameSession = $session->getGameSession();
        if(!$gameSession->isTimerRunning()) return false;
        $player = $session->getPlayer();
        if($player === null) return false;
        $player->sendActionBarMessage("§l§6" . number_format($gameSession->getTimer(), 2) . " Seconds..");
        return true;
    }

    public function onEntityDamage(EntityDamageEvent $event): void{
        if($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $event->setCancelled();
            $player = $event->getEntity();
            if(!$player instanceof Player) return;
            $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
            if(!$gameSession instanceof SpeedClutchGameSession) return;
            if($gameSession->isTimerRunning()){
                $gameSession->stopTimer();
                $gameSession->resetGame();
            } else {
                $player->teleport($gameSession->getSpawn());
            }
        }
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        /** @var PMMPPlayer $player */
        $player = $event->getPlayer();
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof SpeedClutchGameSession || !$player->isOnGround()) return;
        $level = $player->getLevel();
        $block = $level->getBlock($player);
        $blockSideDown = $block->getSide(Vector3::SIDE_DOWN);
        switch($block->getId()) {
            case BlockIds::LIGHT_WEIGHTED_PRESSURE_PLATE: {
                switch($blockSideDown->getId()) {
                    case BlockIds::REDSTONE_BLOCK: {
                        if($gameSession->isTimerRunning()) {
                            $gameSession->stopTimer();
                            $gameSession->updateScore();
                            $gameSession->resetGame();
                            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
                            $ryZerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
                            $player->sendMessage($gameSession->getSettings()->PREFIX.LanguageProvider::getMessageContainer("speedclutch-end-reached", $ryZerPlayer?->getName(true), ["#time" => number_format($gameSession->getScore(), 2)]));
                        }
                        return;
                    }
                    default: {
                        if(!$gameSession->isTimerRunning()) {
                            $gameSession->startTimer();
                            $this->scheduleUpdate($gameSession->getSession());

                            $player->getInventory()->setItem(MinigameDefaultSlots::SLOT_BLOCK_ITEM, Item::get(BlockIds::SANDSTONE, 0, 64));
                            CustomItemManager::getInstance()->getCustomItemByClass(SpeedClutchMinigameResetItem::class)?->giveToPlayer($player, MinigameDefaultSlots::SLOT_OTHER_ITEM);
                        }
                    }
                }
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
        if(!$gameSession instanceof SpeedClutchGameSession) return;
        $block = $event->getBlock();
        if(!$gameSession->isTimerRunning()){
            $event->setCancelled();
            return;
        }
        $gameSession->addBlock($block, "player");
    }
}