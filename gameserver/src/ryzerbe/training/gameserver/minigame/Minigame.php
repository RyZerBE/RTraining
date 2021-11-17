<?php

namespace ryzerbe\training\gameserver\minigame;


use pocketmine\event\Listener;
use pocketmine\Server;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\Training;

abstract class Minigame implements Listener {
    public static string $PREFIX = Training::PREFIX;

    abstract public function getName(): string;

    abstract public function initSettings(): void;

    abstract public function constructGameSession(Session $session): GameSession;

    abstract public function onLoad(Session $session): void;
    abstract public function onUnload(Session $session): void;

    /** @var Session[]  */
    protected array $updatingSessions = [];
    /** @var Session[]  */
    protected array $scheduledUpdatingSessions = [];

    protected MinigameSettings $settings;
    protected MinigameSessionManager $sessionManager;

    public function tick(int $currentTick): void {
        foreach(($this->scheduledUpdatingSessions[$currentTick] ?? []) as $session) {
            $this->updatingSessions[] = $session;
        }
        unset($this->scheduledUpdatingSessions[$currentTick]);
        foreach($this->updatingSessions as $key => $session) {
            if(!$this->onUpdate($session, $currentTick)) unset($this->updatingSessions[$key]);
        }
    }

    public function scheduleUpdate(Session $session, ?int $delay = null): void {
        if($delay !== null) {
            $this->scheduledUpdatingSessions[(Server::getInstance()->getTick() + $delay)][] = $session;
            return;
        }
        $this->updatingSessions[] = $session;
    }

    public function __construct(){
        $this->sessionManager = new MinigameSessionManager($this);
        $this->initSettings();
    }

    public function getSettings(): MinigameSettings{
        return $this->settings;
    }

    public function getSessionManager(): MinigameSessionManager{
        return $this->sessionManager;
    }

    public function onUpdate(Session $session, int $currentTick): bool {
        return false;
    }
}