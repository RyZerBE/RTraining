<?php

namespace ryzerbe\training\lobby\player\setting;

use mysqli;
use pocketmine\Player;
use ryzerbe\core\util\async\AsyncExecutor;

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

    public function saveToDatabase(): void{
        $playerName = $this->getPlayer()->getName();
        $challenge = $this->allowChallengeRequests();
        $team = $this->allowTeamRequests();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($team, $challenge, $playerName): void{
            $mysqli->query("UPDATE `settings` SET team_request='$team',challenge_request='$challenge' WHERE playername='$playerName'");
        });
    }
}