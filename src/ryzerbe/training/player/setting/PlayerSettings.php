<?php

namespace ryzerbe\training\player\setting;

use pocketmine\Player;

class PlayerSettings {

    /** @var Player */
    private Player $player;
    /** @var bool  */
    private bool $teamRequests = true;
    /** @var bool  */
    private bool $challengeRequests = true;

    /**
     * @param Player $player
     */
    public function __construct(Player $player){
        $this->player = $player;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player{
        return $this->player;
    }

    /**
     * @param bool $teamRequests
     */
    public function setTeamRequests(bool $teamRequests): void{
        $this->teamRequests = $teamRequests;
    }

    /**
     * @param bool $challengeRequests
     */
    public function setChallengeRequests(bool $challengeRequests): void{
        $this->challengeRequests = $challengeRequests;
    }

    /**
     * @return bool
     */
    public function allowChallengeRequests(): bool{
        return $this->challengeRequests;
    }

    /**
     * @return bool
     */
    public function allowTeamRequests(): bool{
        return $this->teamRequests;
    }
}