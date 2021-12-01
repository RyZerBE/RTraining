<?php

namespace ryzerbe\training\gameserver\minigame;

use mysqli;
use pocketmine\level\Level;
use pocketmine\Player;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\training\gameserver\game\map\Map;
use ryzerbe\training\gameserver\session\Session;
use function array_merge;
use function method_exists;
use function time;

class MinigameSessionManager {
    private Minigame $minigame;

    /** @var Session[]  */
    private array $sessions = [];

    private int $time = -1;

    public function __construct(Minigame $minigame){
        $this->minigame = $minigame;
    }

    public function getMinigame(): Minigame{
        return $this->minigame;
    }

    /**
     * @return Session[]
     */
    public function getSessions(): array{
        return $this->sessions;
    }

    public function addSession(Session $session): void {
        $this->sessions[$session->getUniqueId()] = $session;
        $minigame = $this->getMinigame();
        $session->setGameSession($minigame->constructGameSession($session));
        $this->time = time();
    }

    public function removeSession(Session $session): void {
        unset($this->sessions[$session->getUniqueId()]);

        $minigame = $this->getMinigame();
        $level = null;
        if(method_exists($session, "getMap")) {
            /** @var Map $map */
            $map = $session->getMap();
            $level = $map->getLevel();
        } elseif(method_exists($minigame, "getLevel")) {
            /** @var Level|null $level */
            $level = $minigame->getLevel();
        }
        $players = [];
        foreach($session->getOnlinePlayers() as $player) $players[$player->getName()] = $player;
        if($level !== null) foreach($level->getPlayers() as $player){
            $players[$player->getName()] = $player;
        }
        foreach($players as $player) {
            $player->getServer()->dispatchCommand($player, "leave");
        }
        $minigame->onUnload($session);

        $duration = time() - $this->time;
        $minigame = $minigame->getName();
        AsyncExecutor::submitMySQLAsyncTask("MinigameStatistics", function(mysqli $mysqli) use ($duration, $minigame): void {
            $mysqli->query("INSERT INTO Training(minigame, duration) VALUES ('$minigame', '$duration')");
        });
    }

    public function isSession(Session $session): bool {
        return isset($this->sessions[$session->getUniqueId()]);
    }

    public function getSessionByPlayer(Player $player): ?Session {
        foreach($this->getSessions() as $session) {
            if($session->isPlayer($player)) return $session;
        }
        return null;
    }
}