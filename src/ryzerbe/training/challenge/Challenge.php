<?php

namespace ryzerbe\training\challenge;

use ryzerbe\training\player\TrainingPlayer;
use ryzerbe\training\team\Team;
use function time;

class Challenge {
    /** @var TrainingPlayer */
    private TrainingPlayer $challenger;
    /** @var string */
    private string $challengedPlayer;

    /** @var Team|null  */
    private ?Team $team;

    /** @var int  */
    private int $createdTime;

    /**
     * @param TrainingPlayer $challenger
     * @param string $challengedPlayer
     * @param Team|null $team
     */
    public function __construct(TrainingPlayer $challenger, string $challengedPlayer, ?Team $team){
        $this->challenger = $challenger;
        $this->team = $team;
        $this->createdTime = time();
        $this->challengedPlayer = $challengedPlayer;
    }

    /**
     * @return Team|null
     */
    public function getTeam(): ?Team{
        return $this->team;
    }

    /**
     * @return TrainingPlayer
     */
    public function getChallenger(): TrainingPlayer{
        return $this->challenger;
    }

    /**
     * @return string
     */
    public function getChallengedPlayerName(): string{
        return $this->challengedPlayer;
    }

    /**
     * @return bool
     */
    public function isValid(): bool{
        return (time() - $this->createdTime) < 20;
    }

    public function remove(): void{
        ChallengeManager::getInstance()->removeChallenge($this->challenger->getPlayer()->getName(), $this->challengedPlayer);
    }
}