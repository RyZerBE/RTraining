<?php

namespace ryzerbe\training\gameserver\game;

use pocketmine\level\Level;
use ryzerbe\training\gameserver\minigame\MinigameSettings;
use ryzerbe\training\gameserver\session\Session;

class GameSession {

    private Session $session;
    private ?Level $level;
    private MinigameSettings $settings;

    public function __construct(Session $session, ?Level $level){
        $this->session = $session;
        $this->level = $level;
        $this->settings = $this->getSession()->getMinigame()->getSettings();
    }

    public function getLevel(): ?Level{
        return $this->level;
    }

    public function setLevel(?Level $level): void{
        $this->level = $level;
    }

    public function getSettings(): MinigameSettings{
        return $this->settings;
    }

    public function getSession(): Session{
        return $this->session;
    }

    public function sendScoreboard(): void{

    }

    public function spawnHolograms(): void{

    }
}