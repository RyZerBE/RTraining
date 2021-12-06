<?php

namespace ryzerbe\training\gameserver\minigame\type\bridger;

use pocketmine\block\BlockIds;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItemManager;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\item\MinigameHubItem;
use ryzerbe\training\gameserver\minigame\Minigame;
use ryzerbe\training\gameserver\minigame\type\bridger\generator\BridgerGenerator;
use ryzerbe\training\gameserver\minigame\type\bridger\item\BridgerMinigameConfigurationItem;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use function in_array;
use function mt_rand;
use function number_format;

class BridgerMinigame extends Minigame {
    public const BASE_Y = 50;

    public const DISTANCE_LIST = [
        //"8" => 8,
        "16" => 16,
        "32" => 32,
        "64" => 64,
        "Endless" => PHP_INT_MAX,
    ];

    public const ROTATION_LIST = [
        "Straight" => 0,
        "Diagonal" => 45,
    ];

    private Level $level;

    /**
     * @throws ReflectionException
     */
    public function __construct(){
        parent::__construct();
        CustomItemManager::getInstance()->registerAll([
            new BridgerMinigameConfigurationItem(Item::get(ItemIds::BOOK)->setCustomName(TextFormat::RED."Settings"), 8),
        ]);

        GeneratorManager::addGenerator(BridgerGenerator::class, "bridger", true);
        Server::getInstance()->generateLevel("Bridger", mt_rand(), BridgerGenerator::class);
        $this->level = Server::getInstance()->getLevelByName("Bridger");
        $this->level->setTime(6000);
        $this->level->stopTime();
    }

    public function getLevel(): Level{
        return $this->level;
    }

    public function getName(): string{
        return "Bridger";
    }

    public function initSettings(): void{
        $this->settings = new BridgerSettings();
    }

    public function constructGameSession(Session $session): GameSession{
        $usedIds = [];
        foreach($this->getSessionManager()->getSessions() as $Session) {
            $gameSession = $Session->getGameSession();
            if($gameSession instanceof BridgerGameSession) $usedIds[] = $gameSession->getPlatformId();
        }
        $id = 0;
        while(in_array($id, $usedIds)) $id++;
        return new BridgerGameSession($session, $this->getLevel(), $id);
    }

    public function onLoad(Session $session): void{
        /** @var BridgerGameSession $gameSession */
        $gameSession = $session->getGameSession();

        /** @var PMMPPlayer $player */
        $player = $session->getPlayer();
        if($player === null) return;

        $player->teleport($gameSession->getSpawn());
        $player->setImmobile(false);
        $player->setGamemode(Player::SURVIVAL);

        $player->getInventory()->setItem(MinigameDefaultSlots::SLOT_BLOCK_ITEM, Item::get(BlockIds::SANDSTONE, 0, 64));
        CustomItemManager::getInstance()->getCustomItemByClass(BridgerMinigameConfigurationItem::class)?->giveToPlayer($player, MinigameDefaultSlots::SLOT_CONFIGURATION_ITEM);
        CustomItemManager::getInstance()->getCustomItemByClass(MinigameHubItem::class)?->giveToPlayer($player, MinigameDefaultSlots::SLOT_LEAVE_ITEM);

        $gameSession->sendScoreboard();
        $this->scheduleUpdate($session);
    }

    public function onUnload(Session $session): void{
        /** @var BridgerGameSession $gameSession */
        $gameSession = $session->getGameSession();

        $gameSession->resetBlocks();
        $gameSession->resetBlocks("platform");

        $player = $session->getPlayer();
        if($player === null) return;

        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
    }

    public function onUpdate(Session $session, int $currentTick): bool{
        /** @var BridgerGameSession $gameSession */
        $gameSession = $session->getGameSession();
        if(!$gameSession->isTimerRunning($gameSession->__getRotation())) return false;
        $player = $session->getPlayer();
        if($player === null) return false;
        $player->sendActionBarMessage("§l§6" . number_format($gameSession->getTimer($gameSession->__getRotation()), 2) . " Seconds..");
        return true;
    }

    public function onPlace(BlockPlaceEvent $event): void{
        $player = $event->getPlayer();
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof BridgerGameSession) return;
        $block = $event->getBlock();
        if($block->getId() !== BlockIds::SANDSTONE){
            $event->setCancelled();
            return;
        }
        if(!$gameSession->isTimerRunning($gameSession->__getRotation())){
            $this->scheduleUpdate($gameSession->getSession());
            $gameSession->startTimer($gameSession->__getRotation());
        }
        //$gameSession->setY($block->getFloorY());
        $player->getInventory()->setItem(MinigameDefaultSlots::SLOT_BLOCK_ITEM, $block->getPickedItem()->setCount(64));
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof BridgerGameSession) return;

        $type = $gameSession->__getRotation();
        if(
            $gameSession->isTimerRunning($type) &&
            $player->isOnGround()
        ) {
            $block = $player->getLevel()->getBlock($player);
            if($block->getId() === BlockIds::LIGHT_WEIGHTED_PRESSURE_PLATE) {
                $gameSession->stopTimer($type);
                $gameSession->updateScore($type);
                $player->playSound("random.levelup", 5.0, 1.0, [$player]);
                $gameSession->reset();
                $player->sendMessage($gameSession->getSettings()->PREFIX.LanguageProvider::getMessageContainer("bridger-end-reached", $player->getName(), ["#time" => number_format($gameSession->getScore($type), 2), "#distance" => $gameSession->getDistance()]));
            }
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void{
        if($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $event->setCancelled();
            $entity = $event->getEntity();
            if(!$entity instanceof Player) return;
            $gameSession = SessionManager::getInstance()->getSessionOfPlayer($entity)?->getGameSession();
            if(!$gameSession instanceof BridgerGameSession) return;
            $gameSession->reset();
        }
    }
}