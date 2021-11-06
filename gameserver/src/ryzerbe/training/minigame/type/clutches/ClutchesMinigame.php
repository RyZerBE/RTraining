<?php

namespace ryzerbe\training\minigame\type\clutches;

use baubolp\core\provider\LanguageProvider;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\training\game\GameSession;
use ryzerbe\training\minigame\item\MinigameHubItem;
use ryzerbe\training\minigame\Minigame;
use ryzerbe\training\minigame\type\clutches\entity\ClutchesEntity;
use ryzerbe\training\minigame\type\clutches\generator\ClutchesGenerator;
use ryzerbe\training\minigame\type\clutches\item\ClutchesStartItem;
use ryzerbe\training\minigame\type\clutches\item\ClutchesStopItem;
use ryzerbe\training\session\Session;
use ryzerbe\training\session\SessionManager;
use ryzerbe\training\Training;
use ryzerbe\training\util\customItem\CustomItemManager;
use ryzerbe\training\util\MinigameDefaultSlots;
use function array_values;
use function count;
use function in_array;
use function microtime;
use function number_format;

class ClutchesMinigame extends Minigame {
    private Level $level;

    public function __construct(){
        parent::__construct();

        Entity::registerEntity(ClutchesEntity::class, true);
        $items = [
            new ClutchesStartItem(Item::get(ItemIds::EMERALD)->setCustomName(TextFormat::DARK_GREEN."Start"),4),
            new ClutchesStopItem(Item::get(ItemIds::REDSTONE_DUST)->setCustomName(TextFormat::RED."Stop"),8),
        ];
        CustomItemManager::getInstance()->registerAll($items);

        GeneratorManager::addGenerator(ClutchesGenerator::class, "clutches", true);
        Server::getInstance()->generateLevel("Clutches", mt_rand(), ClutchesGenerator::class);
        $this->level = Server::getInstance()->getLevelByName("Clutches");
        $this->level->setTime(6000);
        $this->level->stopTime();
    }

    public function tick(int $currentTick): void{
        foreach(HitQueue::getQueue() as $playerName => $hitQueue) {
            foreach($hitQueue as $id => $time) {
                $player = Server::getInstance()->getPlayerExact($playerName);

                if($player === null) {
                    HitQueue::removeQueue($playerName);
                    continue;
                }
                $ms = $time - microtime(true);
                if($ms <= 0) {
                   // $player->sendActionBarMessage(TextFormat::RED.TextFormat::BOLD."HIT!");
                    $session = SessionManager::getInstance()->getSessionOfPlayer($player);
                    if($session === null) {
                        HitQueue::removeQueue($playerName);
                        continue;
                    }
                    /** @var ClutchesGameSession $gameSession */
                    $gameSession = $session->getGameSession();

                    if(count(array_values($hitQueue)) > 1) $ev = new EntityDamageByEntityEvent($gameSession->getSettings()->entity, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0.0, [], 100); //100 = boost y
                    else $ev = new EntityDamageByEntityEvent($gameSession->getSettings()->entity, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0.0, [], $gameSession->getSettings()->knockBackLevel);

                    $player->attack($ev);
                    $gameSession->setLastHitTime(microtime(true));
                    HitQueue::removeQueue($playerName, $id);
                }else {
                    $player->sendActionBarMessage(TextFormat::GOLD.TextFormat::BOLD.number_format($time - microtime(true), 2)." Seconds..");
                    break;
                }
            }
        }
        parent::tick($currentTick);
    }

    public function onUpdate(Session $session, int $currentTick): bool{
        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return false;
        if((int)$gameSession->getLastHit() === 0 || (int)$gameSession->getLastBlock() === 0) return true;

        $player = $session->getPlayer();
        if($player === null) return true;

        $lastHitTimeDifference = microtime(true) - $gameSession->getLastHit();
        $lastBlockTimeDifference = microtime(true) - $gameSession->getLastBlock();
        $spawnDistance = $gameSession->getSpawn()->distanceSquared($player);


        if($lastHitTimeDifference <= 3 && $lastBlockTimeDifference > 0.5 && $spawnDistance > 1 && $gameSession->getLevel()->getBlockIdAt($player->x, $player->y -1, $player->z) === BlockIds::SANDSTONE) {
            $length = (int)$gameSession->getSpawn()->distance(new Vector3($player->x, $gameSession->getSpawn()->y, $player->z));
            if($length > 0){
                $gameSession->setLastHitTime(0);
                $gameSession->setBlockSaveLength($length);
                $gameSession->sendScoreboard();
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer('clutches-length-count', $player->getName(), ['#lengthCount' => $length]));
            }

            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
            HitQueue::addQueue($gameSession);
        }
        return true;
    }

    public function getLevel(): Level{
        return $this->level;
    }

    public function getName(): string{
        return "Clutches";
    }

    public function initSettings(): void{
        $this->settings = new ClutchesSettings();
    }

    public function constructGameSession(Session $session): GameSession{
        $usedIds = [];
        foreach($this->getSessionManager()->getSessions() as $Session) {
            $gameSession = $Session->getGameSession();
            if($gameSession instanceof ClutchesGameSession) $usedIds[] = $gameSession->getPlatformId();
        }
        $id = 0;
        while(in_array($id, $usedIds)) $id++;
        return new ClutchesGameSession($session, $this->getLevel(), $id);
    }

    public function onLoad(Session $session): void{
        /** @var ClutchesGameSession $gameSession */
        $gameSession = $session->getGameSession();

        $player = $session->getPlayer();
        if($player === null) return;

        $player->teleport($gameSession->getSpawn());
        $player->setImmobile(false);
        $player->setGamemode(0);

        $location = $gameSession->getSpawn();
        $location->setComponents($location->x, $location->y, $location->z+1);
        $npc = new ClutchesEntity($location, $player->getSkin());
        $npc->spawnToAll();
        $gameSession->getSettings()->entity = $npc;

        /** @var ClutchesStartItem|null $startItem */
        $startItem = CustomItemManager::getInstance()->getCustomItemByClass(ClutchesStartItem::class);
        /** @var MinigameHubItem|null $leaveItem */
        $leaveItem = CustomItemManager::getInstance()->getCustomItemByClass(MinigameHubItem::class);

        $leaveItem?->giveItem($player, MinigameDefaultSlots::SLOT_LEAVE_ITEM);
        $startItem?->giveItem($player, MinigameDefaultSlots::SLOT_OTHER_ITEM);

        $gameSession->sendScoreboard();
        $this->scheduleUpdate($session);
    }

    public function onUnload(Session $session): void{
        /** @var ClutchesGameSession $gameSession */
        $gameSession = $session->getGameSession();

        $player = $session->getPlayer();
        if($player === null) return;

        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $entity = $gameSession->getSettings()->entity;
        $entity?->flagForDespawn();
        $gameSession->resetBlocks();
    }

    public function onDamage(EntityDamageEvent $event): void{
        $player = $event->getEntity();
        if(!$player instanceof Player) return;
        if($event->getCause() != EntityDamageEvent::CAUSE_VOID) return;

        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return;

        $gameSession->reset();

        HitQueue::removeQueue($player->getName());
        HitQueue::addQueue($gameSession);
    }

    public function onPlace(BlockPlaceEvent $event): void{
        $player = $event->getPlayer();
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return;

        $block = $event->getBlock();
        $player->getInventory()->setItem(MinigameDefaultSlots::SLOT_BLOCK_ITEM, $block->getPickedItem()->setCount(64));
        $gameSession->setLastBlockTime(microtime(true));
    }
}