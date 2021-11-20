<?php

namespace ryzerbe\training\gameserver\minigame\type\mlgrush;

use pocketmine\block\BlockIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\level\Location;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\tile\Bed;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\game\map\Map;
use ryzerbe\training\gameserver\game\time\TimeAPI;
use ryzerbe\training\gameserver\minigame\Minigame;
use ryzerbe\training\gameserver\minigame\trait\MapManagerTrait;
use ryzerbe\training\gameserver\minigame\type\mlgrush\form\VoteSettingsForm;
use ryzerbe\training\gameserver\minigame\type\mlgrush\map\MLGRushMap;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\Countdown;
use ryzerbe\training\gameserver\util\Logger;
use function count;
use function implode;

class MLGRushMinigame extends Minigame {
    use MapManagerTrait;

    public const POINTS_LIST = [
        "5" => 5,
        "10" => 10,
        "15" => 15,
        "30" => 30,
        "Endless" => PHP_INT_MAX,
    ];

    public function __construct(){
        $this->loadMaps();
        parent::__construct();
    }

    public function loadMaps(): void{
        $this->mapPool = [
            new MLGRushMap("Line", "Neruxvace", [
                "Team 1" => new Location(128.5, 100, 128.5, 180.0, 0.0),
                "Team 2" => new Location(128.5, 100, 98.5, 0.0, 0.0),
            ], new Location(128.5, 70, 10.5, 0.0, 0.0), $this, 92, 112)
        ];
    }

    public function getName(): string{
        return "MLGRush";
    }

    public function initSettings(): void{
        $this->settings = new MLGRushSettings();
    }

    public function constructGameSession(Session $session): GameSession{
        $this->setMap(new Map($this->getRandomMap(), $session));
        return new MLGRushGameSession($session, null);
    }

    public function onUpdate(Session $session, int $currentTick): bool{
        if($currentTick % 20 !== 0) return true;
        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof MLGRushGameSession) return false;
        $countdown = $gameSession->getCountdown();
        if($countdown !== null) {
            switch($countdown->getState()){
                case MLGRushGameSession::STATE_VOTING: {
                    switch($countdown->getCountdown()) {
                        case 10: {
                            foreach($session->getOnlinePlayers() as $player) {
                                $player->sendTitle(TextFormat::DARK_AQUA."VOTING");
                                $player->playSound("beacon.activate", 5.0, 1.0, [$player]);
                            }
                            break;
                        }
                        case 7: {
                            foreach($session->getOnlinePlayers() as $player) {
                                VoteSettingsForm::open($player);
                            }
                            break;
                        }
                        case 6: {
                            $playerCount = count($session->getPlayers());
                            if($playerCount <= 2) {
                                if($gameSession->getVotes() === $playerCount) {
                                    $countdown->setCountdown(0);
                                    break;
                                }
                                return true;
                            }
                            if($gameSession->getVotes() < ($playerCount / 2)) {
                                //Waiting for 50% of the players to vote
                                return true;
                            }
                            break;
                        }
                    }
                    break;
                }
            }

            $countdown->tick();
            if($countdown->getCountdown() <= 5) {
                $color = match (true) {
                    $countdown->getCountdown() === 3 => TextFormat::GREEN,
                    $countdown->getCountdown() === 2 => TextFormat::YELLOW,
                    $countdown->getCountdown() === 1 => TextFormat::RED,
                    default => TextFormat::AQUA
                };
                foreach($session->getOnlinePlayers() as $player) {
                    $player->sendTitle($color.$countdown->getCountdown());
                }
            }

            if($countdown->hasFinished()) {
                switch($countdown->getState()) {
                    case Countdown::START: {
                        $gameSession->stopCountdown();
                        $gameMap = $this->getMap()->getGameMap();
                        $level = $this->getMap()->getLevel();
                        foreach($session->getTeams() as $team) {
                            $location = $gameMap->getTeamLocation($team->getName(), $level);
                            if($location === null) continue;
                            foreach($team->getPlayers() as $player) {
                                $player->playSound("random.explode", 5.0, 1.0, [$player]);
                                $player->setImmobile(false);
                                $player->sendTitle(TextFormat::DARK_AQUA."LET'S FIGHT!", TextFormat::GRAY."Good luck!");
                                $gameSession->resetPlayer($player);
                            }
                        }
                        $gameSession->sendScoreboard();
                        $gameSession->setRunning(true);
                        break;
                    }
                    case Countdown::END: {
                        SessionManager::getInstance()->removeSession($session);
                        $session->getMinigame()->getSessionManager()->removeSession($session);
                        return false;
                    }
                    case MLGRushGameSession::STATE_VOTING: {
                        $gameSession->validateVoting();
                        foreach($session->getOnlinePlayers() as $player) {
                            $player->sendTitle(TextFormat::DARK_AQUA."VOTING ENDED", TextFormat::GREEN.implode("\n".TextFormat::GREEN, [
                                    "Points ".TextFormat::GRAY."» ".($gameSession->getMaxPoints() >= PHP_INT_MAX ? TextFormat::RED."Endless" : TextFormat::GREEN.$gameSession->getMaxPoints()),
                                    "Infinite Blocks ".TextFormat::GRAY."» ".($gameSession->isInfiniteBlocks() ? TextFormat::GREEN."Enabled" : TextFormat::RED."Disabled")
                                ]));
                        }
                        $gameSession->startCountdown(8, Countdown::START);
                        $gameSession->setState(Countdown::START);
                        break;
                    }
                }
            }
            return true;
        }
        $gameSession->tick++;
        foreach($session->getOnlinePlayers() as $player) {
            $player->sendActionBarMessage(TextFormat::GRAY.TimeAPI::convert($gameSession->tick)->asString()."\n".(
                    $gameSession->getSettings()->elo ? LanguageProvider::getMessageContainer("elo-enabled", $player) : TextFormat::AQUA."discord.ryzer.be"
                ));
        }
        return true;
    }

    public function onLoad(Session $session): void{
        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof MLGRushGameSession) return;

        foreach($session->getOnlinePlayers() as $player) {
            $player->setImmobile(true);
        }

        $elo = $gameSession->getSettings()->elo;
        if($elo) {
            $gameSession->setMaxPoints(5);
            $gameSession->setInfiniteBlocks(false);
        }

        $map = $this->getMap();
        $map->load(function() use ($map, $session, $gameSession, $elo): void {
            $gameMap = $map->getGameMap();
            $level = $this->getMap()->getLevel();
            $session->getGameSession()->setLevel($level);
            foreach($session->getTeams() as $team) {
                $location = $gameMap->getTeamLocation($team->getName(), $level);
                if($location === null){
                    Logger::error("Team " . $team->getName() . " is not valid!");
                    continue;
                }
                if($elo) $gameSession->loadTeamElo($team, $this->getName());
                $color = $team->getColor();
                foreach($team->getPlayers() as $player) {
                    $player->setGamemode(0);
                    $player->setHealth($player->getMaxHealth());
                    $player->setFood($player->getMaxFood());
                    $player->teleport($gameMap->getTeamLocation($team->getName(), $level));
                    $player->setImmobile(true);
                    $player->setNameTag($color.$player->getName());
                    $player->setDisplayName($color.$player->getName());
                }
            }
            if($elo) {
                $this->scheduleUpdate($session);
                $gameSession->startCountdown(10, Countdown::START);
            } else {
                $this->scheduleUpdate($session, 40);
                $gameSession->setState(MLGRushGameSession::STATE_VOTING);
                $gameSession->startCountdown(10, MLGRushGameSession::STATE_VOTING);
            }
        });
    }

    public function onUnload(Session $session): void{
        foreach($session->getOnlinePlayers() as $player) {
            $player->getServer()->dispatchCommand($player, "leave");
        }
        $this->getMap()->unload();
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        $gameSession = $session?->getGameSession();
        if(!$gameSession instanceof MLGRushGameSession || !$gameSession->isRunning()) return;
        /** @var MLGRushMap $gameMap */
        $gameMap = $this->map->getGameMap();
        if($player->getY() < $gameMap->getDeathHeight()) {
            $gameSession->resetPlayer($player);
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        $gameSession = $session?->getGameSession();
        if(!$gameSession instanceof MLGRushGameSession || !$gameSession->isRunning()) return;
        /** @var MLGRushMap $gameMap */
        $gameMap = $this->map->getGameMap();
        $block = $event->getBlock();
        if($block->getY() > $gameMap->getBuildHeight() || $block->getY() < $gameMap->getDeathHeight()) {
            $event->setCancelled();
            return;
        }
        if($gameSession->isInfiniteBlocks()) {
            $player->getInventory()->setItemInHand($event->getItem()->setCount(64));
        }
    }

    /**
     * @ignoreCancelled false
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        $gameSession = $session?->getGameSession();
        if(!$gameSession instanceof MLGRushGameSession || !$gameSession->isRunning()) return;
        /** @var MLGRushMap $gameMap */
        $gameMap = $this->map->getGameMap();
        $block = $event->getBlock();
        if($block->getY() > $gameMap->getBuildHeight()) {
            $event->setCancelled();
            return;
        }

        if($block->getId() === BlockIds::BED_BLOCK) {
            $team = $session->getTeamByPlayer($player);
            $tile = $player->getLevel()->getTile($block);
            if(!$tile instanceof Bed) return;
            $event->setCancelled();
            if($team->getBlockMeta() === $tile->getColor()) {
                $player->getLevel()->addParticle(new SmokeParticle($block->floor()->add(0.5, 0.5, 0.5)));
                $player->playSound("note.bass", 5.0, 1.0, [$player]);

                $player->sendMessage($gameSession->getSettings()->PREFIX.LanguageProvider::getMessageContainer("break-own-bed", $player));
                return;
            }
            $gameSession->resetAll();
            $gameSession->addPoints($team, 1);
            $gameSession->sendScoreboard();
            $gameSession->checkGameEnd();

            foreach($session->getOnlinePlayers() as $player) {
                $player->playSound("firework.twinkle", 5.0, 1.0, [$player]);
            }
        }

        if(!$event->isCancelled()) {
            if($gameSession->isInfiniteBlocks()){
                $event->setDrops([]);
            } else {
                $drops = [];
                foreach($event->getDrops() as $drop) {
                    $drop->setCustomName("§r§a" . $drop->getVanillaName());
                }
            }
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        $gameSession = $session?->getGameSession();
        if(!$gameSession instanceof MLGRushGameSession) return;
        $event->setDeathMessage("");
        $event->setKeepInventory(true);
        $event->setKeepExperience(true);
        $gameSession->checkGameEnd(!$gameSession->isRunning());
    }

    public function onPlayerRespawn(PlayerRespawnEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        $gameSession = $session?->getGameSession();
        if(!$gameSession instanceof MLGRushGameSession || !$gameSession->isRunning()) return;
        $gameSession->resetPlayer($player);
    }
}