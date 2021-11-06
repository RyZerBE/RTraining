<?php

namespace ryzerbe\training\gameserver\minigame\type\mlgrush;

use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\Minigame;
use ryzerbe\training\gameserver\session\Session;

class MLGRushMinigame extends Minigame {

    /**
     * @return string
     */
    public function getName(): string{
        return "MLGRush";
    }

    /**
     *
     */
    public function initSettings(): void{
        $this->settings = new MLGRushSettings();
    }

    /**
     * @param Session $session
     * @return GameSession
     */
    public function constructGameSession(Session $session): GameSession{
        // TODO: Implement constructGameSession() method.
    }

    /**
     * @param Session $session
     */
    public function onLoad(Session $session): void{
        // TODO: Implement onLoad() method.
    }

    /**
     * @param Session $session
     */
    public function onUnload(Session $session): void{
        // TODO: Implement onUnload() method.
    }
}