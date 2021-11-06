<?php

namespace ryzerbe\training\session;

use pocketmine\Player;
use pocketmine\utils\SingletonTrait;

class SessionManager {
    use SingletonTrait;

    /** @var Session[]  */
    private array $sessions = [];

    /**
     * @return Session[]
     */
    public function getSessions(): array{
        return $this->sessions;
    }

    public function getSessionOfPlayer(Player $player): ?Session {
        foreach($this->getSessions() as $session) {
            if($session->isPlayer($player)) return $session;
        }
        return null;
    }

    public function getSession(string $uniqueId): ?Session {
        return $this->sessions[$uniqueId] ?? null;
    }

    public function addSession(Session $session): void {
        $this->sessions[$session->getUniqueId()] = $session;
    }

    public function removeSession(Session $session): void {
        unset($this->sessions[$session->getUniqueId()]);
    }
}