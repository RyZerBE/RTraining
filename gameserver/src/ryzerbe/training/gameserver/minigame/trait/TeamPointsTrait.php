<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\trait;

use ryzerbe\training\gameserver\game\team\Team;

trait TeamPointsTrait {
    private array $points = [];

    public function getPoints(Team $team): int{
        return $this->points[$team->getId()] ?? 0;
    }

    public function setPoints(Team $team, int $points): void{
        $this->points[$team->getId()] = $points;
    }

    public function addPoints(Team $team, int $points): void{
        $this->setPoints($team, $this->getPoints($team) + $points);
    }

    public function removePoints(Team $team, int $points): void{
        $this->setPoints($team, $this->getPoints($team) - $points);
    }

}