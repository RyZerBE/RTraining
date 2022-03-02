<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches;

use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItemManager;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\item\MinigameHubItem;
use ryzerbe\training\gameserver\minigame\Minigame;
use ryzerbe\training\gameserver\minigame\type\clutches\entity\ClutchesEntity;
use ryzerbe\training\gameserver\minigame\type\clutches\generator\ClutchesGenerator;
use ryzerbe\training\gameserver\minigame\type\clutches\item\ClutchesConfigurationItem;
use ryzerbe\training\gameserver\minigame\type\clutches\item\ClutchesStartItem;
use ryzerbe\training\gameserver\minigame\type\clutches\item\ClutchesStopItem;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\Training;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use function in_array;
use function number_format;

class ClutchesMinigame extends Minigame {
    public const ONE_HIT = 1;
    public const DOUBLE_HIT = 2;
    public const TRIPLE_HIT = 3;
    public const QUADRUPLE_HIT = 4;

    public const EASY = 0.7;
    public const NORMAL = 1.0;
    public const HARD = 1.3;

    public const HIT_DELAY = 10;

    public const HIT_TYPES = [
        "One hit" => self::ONE_HIT,
        "Double hit" => self::DOUBLE_HIT,
        "Triple hit" => self::TRIPLE_HIT,
        "Quadruple hit" => self::QUADRUPLE_HIT,
    ];

    public const KNOCKBACK_LEVELS = [
        "Easy" => self::EASY,
        "Normal" => self::NORMAL,
        "Hard" => self::HARD,
    ];

    private Level $level;

    /**
     * @throws ReflectionException
     */
    public function __construct(){
        parent::__construct();

        Entity::registerEntity(ClutchesEntity::class, true);
        $items = [
            new ClutchesStartItem(Item::get(ItemIds::RECORD_FAR)->setCustomName(TextFormat::DARK_GREEN."Start"),4),
            new ClutchesStopItem(Item::get(ItemIds::RECORD_MALL)->setCustomName(TextFormat::RED."Stop"),4),
            new ClutchesConfigurationItem(Item::get(ItemIds::BOOK)->setCustomName(TextFormat::RED."Settings"), 8),
        ];
        CustomItemManager::getInstance()->registerAll($items);

        GeneratorManager::addGenerator(ClutchesGenerator::class, "clutches", true);
        Server::getInstance()->generateLevel("Clutches", mt_rand(), ClutchesGenerator::class);
        $this->level = Server::getInstance()->getLevelByName("Clutches");
        $this->level->setTime(6000);
        $this->level->stopTime();
    }

    public function onUpdate(Session $session, int $currentTick): bool{
        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return false;
        $player = $session->getPlayer();
        if(!$player instanceof PMMPPlayer) return false;
        if($gameSession->isRunning()) {
            $countdown = $gameSession->getCountdown();
            switch($gameSession->getState()) {
                case ClutchesGameSession::STATE_COUNTDOWN: {
                    $player->sendActionBarMessage(TextFormat::GOLD.TextFormat::BOLD.number_format($countdown->getCountdown() / 20, 2)." Seconds..");
                    if($countdown->hasFinished()) {
                        $countdown->resetCountdown(($gameSession->getHitType() * self::HIT_DELAY) - self::HIT_DELAY);
                        $gameSession->setState(ClutchesGameSession::STATE_HITTING);
                        return true;
                    }
                    $countdown->tick();
                    break;
                }
                case ClutchesGameSession::STATE_HITTING: {
                    if($countdown->hasFinished()) {
                        switch($countdown->getCountdown()) {
                            case 0: {
                                $countdown->setCountdown(-1);
                                $player->addDelay("clutches_cooldown", 0.25);

                                $ev = new EntityDamageByEntityEvent($gameSession->getEntity(), $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0.0, [], 10);
                                $player->attack($ev);
                                return true;
                            }
                            default: {
                                if($player->hasDelay("clutches_cooldown")) return true;
                                if($player->isOnGround()) {
                                    $length = (int)$gameSession->getSpawn()->distance(new Vector3($player->x, $gameSession->getSpawn()->y, $player->z));
                                    if($length > 2) {
                                        $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer('clutches-length-count', $player->getName(), ['#lengthCount' => $length]));
                                        $player->playSound("random.levelup", 5.0, 1.0, [$player]);
                                        $gameSession->setBlockSaveLength($length);
                                        $gameSession->sendScoreboard();
                                    }

                                    $countdown->resetCountdown();
                                    $gameSession->setState(ClutchesGameSession::STATE_COUNTDOWN);
                                }
                            }
                        }
                        return true;
                    }
                    if($countdown->getCountdown() % self::HIT_DELAY === 0) {
                        $ev = new EntityDamageByEntityEvent($gameSession->getEntity(), $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0.0, [], $gameSession->getKnockBackLevel());
                        $player->attack($ev);
                    }
                    $countdown->tick();
                    break;
                }
            }
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

        /** @var PMMPPlayer $player */
        $player = $session->getPlayer();
        if($player === null) return;

        $player->teleport($gameSession->getSpawn());
        $player->setImmobile(false);
        $player->setGamemode(0);

        $location = $gameSession->getSpawn();
        $location->setComponents($location->x, $location->y, $location->z + 1);
        $npc = new ClutchesEntity($location, $player->getSkin(), $gameSession->getPlatformId());
        $npc->spawnToAll();

        $startItem = CustomItemManager::getInstance()->getCustomItemByClass(ClutchesStartItem::class);
        $configurationItem = CustomItemManager::getInstance()->getCustomItemByClass(ClutchesConfigurationItem::class);
        $leaveItem = CustomItemManager::getInstance()->getCustomItemByClass(MinigameHubItem::class);

        $leaveItem?->giveToPlayer($player, MinigameDefaultSlots::SLOT_LEAVE_ITEM);
        $startItem?->giveToPlayer($player, MinigameDefaultSlots::SLOT_OTHER_ITEM);
        $configurationItem?->giveToPlayer($player, MinigameDefaultSlots::SLOT_CONFIGURATION_ITEM);

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
        $gameSession->getEntity()?->flagForDespawn();
        $gameSession->resetBlocks();
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return;
        $event->setDeathMessage("");

        $gameSession->reset();
    }

    public function onPlayerRespawn(PlayerRespawnEvent $event): void {
        $player = $event->getPlayer();
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return;
        $player->noDamageTicks = 0;
    }
}