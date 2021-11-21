<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\minigame\setting;

use pocketmine\entity\Skin;
use pocketmine\level\Location;

class NPCSettings {
    private Location $location;
    private Skin $skin;

    private string $title;

    private ?string $group;
    private ?string $queue;

    public function __construct(Location $location, Skin $skin, string $title, ?string $group = null, ?string $queue = null){
        $this->location = $location;
        $this->skin = $skin;
        $this->title = $title;
        $this->group = $group;
        $this->queue = $queue;
    }

    public function getLocation(): Location{
        return $this->location;
    }

    public function getSkin(): Skin{
        return $this->skin;
    }

    public function getTitle(): string{
        return $this->title;
    }

    public function getGroup(): ?string{
        return $this->group;
    }

    public function getQueue(): ?string{
        return $this->queue;
    }
}