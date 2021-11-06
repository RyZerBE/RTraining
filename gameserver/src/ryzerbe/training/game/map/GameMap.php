<?php

namespace ryzerbe\training\game\map;

use pocketmine\level\Level;
use pocketmine\level\Location;
use ryzerbe\training\minigame\Minigame;

class GameMap {
    private string $mapName;
    private string $creator;

    private array $teamLocations;
    private Location $spectatorLocation;

    private Minigame $minigame;

    public function __construct(string $mapName, string $creator, array $teamLocations, Location $spectatorLocation, Minigame $minigame){
        $this->minigame = $minigame;
        $this->creator = $creator;
        $this->teamLocations = $teamLocations;
        $this->spectatorLocation = $spectatorLocation;
        $this->mapName = $mapName;
    }

    public function getMinigame(): Minigame{
        return $this->minigame;
    }

    public function getCreator(): string{
        return $this->creator;
    }

    public function getMapName(): string{
        return $this->mapName;
    }

    public function getSpectatorLocation(Level $level): Location{
        $this->spectatorLocation->level = $level;
        return $this->spectatorLocation;
    }

    public function getTeamLocations(): array{
        return $this->teamLocations;
    }

    public function getTeamLocation(string $teamName, Level $level){
        $location = $this->getTeamLocations()[$teamName] ?? null;
        if($location === null) return null;
        $location->level = $level;
        return $location;
    }
}