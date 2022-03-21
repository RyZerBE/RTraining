<?php

namespace ryzerbe\training\gameserver\minigame\type\kitpvp;

use pocketmine\block\BlockIds;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\SplashPotion;
use pocketmine\level\Location;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\data\LoginPlayerData;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\util\TextUtils;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\game\map\GameMap;
use ryzerbe\training\gameserver\game\map\Map;
use ryzerbe\training\gameserver\game\time\TimeAPI;
use ryzerbe\training\gameserver\minigame\Minigame;
use ryzerbe\training\gameserver\minigame\trait\MapManagerTrait;
use ryzerbe\training\gameserver\minigame\type\kitpvp\kits\KitManager;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\Countdown;
use ryzerbe\training\gameserver\util\Logger;
use function in_array;
use function min;

class KitPvPMinigame extends Minigame {
    use MapManagerTrait;

    public function __construct(){
        KitManager::getInstance()->loadKits(function(): void {});
        $this->loadMaps();
        parent::__construct();
    }

    public function loadMaps(): void{
        $this->mapPool = [
            new GameMap("Emerald", "Unknown", [
                new Location(-1.5, 65, 39.5, 180.0, 0.0),
                new Location(0.5, 65, -36.5, 0.0, 0.0),
            ], new Location(0.5, 70, 1.5, 0.0, 0.0), $this)
        ];
    }

    public function onUpdate(Session $session, int $currentTick): bool{
        if($currentTick % 20 !== 0) return true;
        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof KitPvPGameSession) return false;
        $countdown = $gameSession->getCountdown();
        if($countdown !== null) {
            $countdown->tick();
            $color = match (true) {
                $countdown->getCountdown() === 3 => TextFormat::GREEN,
                $countdown->getCountdown() === 2 => TextFormat::YELLOW,
                $countdown->getCountdown() === 1 => TextFormat::RED,
                default => TextFormat::AQUA
            };
            if($countdown->getCountdown() <= 5) {
                foreach($session->getOnlinePlayers() as $player) {
                    $player->sendTitle($color.$countdown->getCountdown());
                }
            }
            if($countdown->hasFinished()) {
                if($countdown->getState() === Countdown::START) {
                    $gameSession->setRunning(true);
                    $gameSession->stopCountdown();
                    $gameMap = $gameSession->getMap()->getGameMap();
                    $level = $gameSession->getMap()->getLevel();
                    foreach($session->getTeams() as $team) {
                        $location = $gameMap->getTeamLocation($team->getId(), $level);
                        if($location === null) continue;
                        foreach($team->getPlayers() as $player) {
                            $player->playSound("random.explode", 5.0, 1.0, [$player]);
                            $player->setImmobile(false);
                            $player->sendTitle(TextFormat::DARK_AQUA."LET'S FIGHT!", TextFormat::GRAY."Good luck!");
                            if($player->distanceSquared($location) > 0.25) {
                                $player->teleport($gameMap->getTeamLocation($team->getId(), $level));
                            }
                        }
                    }
                }else {
                    SessionManager::getInstance()->removeSession($session);
                    $session->getMinigame()->getSessionManager()->removeSession($session);
                    return false;
                }
            }
            return true;
        }
        $gameSession->tick++;
        foreach($session->getOnlinePlayers() as $player) {
            $player->sendActionBarMessage(TextUtils::formatEol(TextFormat::GRAY.TimeAPI::convert($gameSession->tick)->asString()."\n".(
                    $gameSession->getSettings()->elo ? LanguageProvider::getMessageContainer("elo-enabled", $player) : TextFormat::AQUA."discord.ryzer.be"
                )));
        }
        return true;
    }

    public function getName(): string{
        return "KitPvP";
    }

    public function initSettings(): void{
        $this->settings = new KitPvPSettings();
    }

    public function constructGameSession(Session $session): GameSession{
        $this->setMap(new Map($this->getRandomMap(), $session));
        return new KitPvPGameSession($session, null);
    }

    public function onLoad(Session $session): void{
        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof KitPvPGameSession) return;
        $kit = KitManager::getInstance()->getKitByName($session->getExtraData()["kitName"] ?? "Bastard");
        if($kit !== null) $gameSession->setKit($kit);
        $gameSession->loadPlayerKits();

        foreach($session->getOnlinePlayers() as $player) {
            $player->setImmobile(true);
        }

        $gameSession->setMap($this->getMap());
        $map = $gameSession->getMap();
        $map->load(function() use ($map, $session, $gameSession): void {
            $gameMap = $map->getGameMap();
            $level = $gameSession->getMap()->getLevel();
            $session->getGameSession()->setLevel($level);
            foreach($session->getTeams() as $team) {
                $location = $gameMap->getTeamLocation($team->getId(), $level);
                if($location === null){
                    Logger::error("Team " . $team->getName() . " is not valid!");
                    continue;
                }
                $color = $team->getColor();
                $gameSession->loadTeamElo($team, $this->getName());
                foreach($team->getPlayers() as $player) {
                    $player->setGamemode(0);
                    $player->setHealth($player->getMaxHealth());
                    $player->setFood($player->getMaxFood());
                    $player->teleport($gameMap->getTeamLocation($team->getId(), $level));
                    $player->setImmobile(true);
                    $player->setNameTag($color.$player->getName());
                    $player->setDisplayName($color.$player->getName());
                }
            }

            $gameSession->startCountdown(10, Countdown::START);
            $this->scheduleUpdate($session);
        });
    }

    public function onUnload(Session $session): void{
        $session->getGameSession()->getMap()->unload();
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        $gameSession = $session?->getGameSession();
        if(!$gameSession instanceof KitPvPGameSession) return;
        $session->getTeamByPlayer($player)?->removePlayer($player);
        $gameSession->checkGameEnd(!$gameSession->isRunning());
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        $gameSession = $session?->getGameSession();
        if(!$gameSession instanceof KitPvPGameSession) return;

        if(!$gameSession->isRunning()) {
            $event->setCancelled();
            return;
        }

        $item = $event->getItem();
        $inventory = $player->getInventory();
        switch(true) {
           case ($item instanceof SplashPotion): {
               $inventory->setItemInHand(Item::get(0));
               break;
           }
            case ($item->getId() === ItemIds::MUSHROOM_STEW): {
                if((int)$player->getHealth() === $player->getMaxHealth()) return;
                $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($player);
                $player->setHealth(min(($player->getHealth() + 4.5), 20));
                if($rbePlayer === null) {
                    $inventory->setItemInHand(Item::get(ItemIds::BOWL));
                }else {
                    $os = [LoginPlayerData::TOUCH, LoginPlayerData::CONTROLLER];
                    $inventory->setItemInHand(in_array($rbePlayer->getLoginPlayerData()->getCurrentInputMode(), $os) ? Item::get(BlockIds::AIR) : Item::get(ItemIds::BOWL));
                }
                $player->playSound("random.eat", 2.0, 1.0, [$player]);
                break;
            }
        }
    }
}