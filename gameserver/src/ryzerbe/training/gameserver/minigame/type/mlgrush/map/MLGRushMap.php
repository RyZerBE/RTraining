<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\mlgrush\map;

use pocketmine\block\Block;
use pocketmine\level\Location;
use ryzerbe\training\gameserver\game\map\GameMap;
use ryzerbe\training\gameserver\minigame\Minigame;
use function array_rand;

class MLGRushMap extends GameMap {
    private int $buildHeight;
    private int $deathHeight;

    /** @var Location[] */
    private array $wallLocations;
    /** @var Block[] */
    private array $wallBlocks;

    public function __construct(string $mapName, string $creator, array $teamLocations, Location $spectatorLocation, Minigame $minigame, int $deathHeight, int $buildHeight, array $wallLocations = [], array $wallBlocks = []){
        $this->deathHeight = $deathHeight;
        $this->buildHeight = $buildHeight;
        $this->wallLocations = $wallLocations;
        $this->wallBlocks = $wallBlocks;
        parent::__construct($mapName, $creator, $teamLocations, $spectatorLocation, $minigame);
    }

    public function getBuildHeight(): int{
        return $this->buildHeight;
    }

    public function getDeathHeight(): int{
        return $this->deathHeight;
    }

    /**
     * @return Location[]
     */
    public function getWallLocations(): array{
        return $this->wallLocations;
    }

    /**
     * @return Block[]
     */
    public function getWallBlocks(): array{
        return $this->wallBlocks;
    }

    public function getRandomWallBlock(): Block {
        return $this->wallBlocks[array_rand($this->wallBlocks)];
    }
}