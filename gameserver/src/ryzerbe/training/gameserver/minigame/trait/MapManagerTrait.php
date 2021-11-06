<?php

namespace ryzerbe\training\gameserver\minigame\trait;

use ryzerbe\training\gameserver\game\map\GameMap;
use ryzerbe\training\gameserver\game\map\Map;
use function array_rand;

trait MapManagerTrait {
    /** @var GameMap[]  */
    protected array $mapPool;

    protected ?Map $map;

    public function getMap(): ?Map{
        return $this->map;
    }

    public function setMap(?Map $map): void{
        $this->map = $map;
    }

    /**
     * @return GameMap[]
     */
    public function getMapPool(): array{
        return $this->mapPool;
    }

    public function getRandomMap(): GameMap {
        return $this->mapPool[array_rand($this->mapPool)];
    }

    abstract public function loadMaps(): void;
}