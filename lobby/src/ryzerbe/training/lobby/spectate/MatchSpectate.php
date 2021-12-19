<?php

namespace ryzerbe\training\lobby\spectate;

class MatchSpectate {

    /**
     * @param string $matchId
     * @param array $teams
     * @param array $playerNames
     * @param string $miniGame
     */
    public function __construct(private string $matchId, private array $teams, private array $playerNames, private string $miniGame){}

    /**
     * @return string
     */
    public function getMiniGame(): string{
        return $this->miniGame;
    }

    /**
     * @return array
     */
    public function getPlayerNames(): array{
        return $this->playerNames;
    }

    /**
     * @return array
     */
    public function getTeams(): array{
        return $this->teams;
    }

    /**
     * @return string
     */
    public function getMatchId(): string{
        return $this->matchId;
    }
}