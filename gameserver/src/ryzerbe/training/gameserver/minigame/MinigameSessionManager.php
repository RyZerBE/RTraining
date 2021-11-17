<?php

namespace ryzerbe\training\gameserver\minigame;

use mysqli;
use pocketmine\Player;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\training\gameserver\session\Session;
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
        $this->getMinigame()->onUnload($session);

        $duration = time() - $this->time;
        $minigame = $this->getMinigame()->getName();
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