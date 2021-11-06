<?php

namespace ryzerbe\training\minigame;

use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\training\Training;

class MinigameManager {

    /** @var Minigame[]  */
    public static array $minigames = [];

    /**
     * @return Minigame[]
     */
    public static function getMinigames(): array{
        return self::$minigames;
    }

    public static function registerMinigame(Minigame $minigame){
        self::$minigames[$minigame->getName()] = $minigame;
        Server::getInstance()->getPluginManager()->registerEvents($minigame, Training::getInstance());
    }

    public static function unregisterMinigame(Minigame|string $minigame){
        if($minigame instanceof Minigame) $minigame = $minigame->getName();
        unset(self::$minigames[$minigame]);
    }

    public static function getMinigame(string $minigameName): ?Minigame{
        return self::$minigames[$minigameName] ?? null;
    }

    public static function getMinigameByLevel(Level $level): ?Minigame {
        $levelId = $level->getId();
        foreach(self::getMinigames() as $minigame) {
            foreach($minigame->getSessionManager()->getSessions() as $session) {
                $gameSession = $session->getGameSession();
                if($gameSession !== null && $gameSession->getLevel()?->getId() === $levelId) return $minigame;
            }
        }
        return null;
    }

    public static function getMinigameByPlayer(Player $player): ?Minigame {
        foreach(self::getMinigames() as $minigame) {
            foreach($minigame->getSessionManager()->getSessions() as $session) {
                if($session->isPlayer($player)) return $minigame;
            }
        }
        return null;
    }
}