<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\mlgrush\map;

use pocketmine\level\Location;
use ryzerbe\training\gameserver\game\map\GameMap;
use ryzerbe\training\gameserver\minigame\Minigame;

class MLGRushMap extends GameMap {
    private int $buildHeight;
    private int $deathHeight;

    public function __construct(string $mapName, string $creator, array $teamLocations, Location $spectatorLocation, Minigame $minigame, int $deathHeight, int $buildHeight){
        $this->deathHeight = $deathHeight;
        $this->buildHeight = $buildHeight;
        parent::__construct($mapName, $creator, $teamLocations, $spectatorLocation, $minigame);
    }

    public function getBuildHeight(): int{
        return $this->buildHeight;
    }

    public function getDeathHeight(): int{
        return $this->deathHeight;
    }
}