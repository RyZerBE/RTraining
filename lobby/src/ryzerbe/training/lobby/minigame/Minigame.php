<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\minigame;

use Closure;
use ryzerbe\training\lobby\minigame\setting\NPCSettings;

class Minigame {
    private string $name;

    private ?string $group = null;

    private bool $queue = false;
    private bool $elo = false;
    private bool $multiplayer = false;

    private bool $released = true;
    private bool $teaser = false;
    private bool $beta = false;

    private ?Closure $settings = null;

    private ?array $items = null;
    private ?NPCSettings $npcSettings = null;

    public function __construct(string $name){
        $this->name = $name;
    }

    public function getName(): string{
        return $this->name;
    }

    public function getGroup(): ?string{
        return $this->group;
    }

    public function setGroup(?string $group): self{
        $this->group = $group;
        return $this;
    }

    public function hasQueue(): bool{
        return $this->queue;
    }

    public function setQueue(bool $queue): self{
        $this->queue = $queue;
        return $this;
    }

    public function isElo(): bool{
        return $this->elo;
    }

    public function setElo(bool $elo): self{
        $this->elo = $elo;
        return $this;
    }

    public function isMultiplayer(): bool{
        return $this->multiplayer;
    }

    public function setMultiplayer(bool $multiplayer): self{
        $this->multiplayer = $multiplayer;
        return $this;
    }

    public function isReleased(): bool{
        return $this->released;
    }

    public function setReleased(bool $released): self{
        $this->released = $released;
        return $this;
    }

    public function isBeta(): bool{
        return $this->beta;
    }

    public function setBeta(bool $beta): self{
        $this->beta = $beta;
        return $this;
    }

    public function isTeaser(): bool{
        return $this->teaser;
    }

    public function setTeaser(bool $teaser): self{
        $this->teaser = $teaser;
        return $this;
    }

    public function getItems(): ?array{
        return $this->items;
    }

    public function setItems(?array $items): self{
        $this->items = $items;
        return $this;
    }

    public function getNpcSettings(): ?NPCSettings{
        return $this->npcSettings;
    }

    public function setNpcSettings(?NPCSettings $npcSettings): self{
        $this->npcSettings = $npcSettings;
        return $this;
    }

    public function getSettings(): ?Closure{
        return $this->settings;
    }

    public function setSettings(?Closure $settings): self{
        $this->settings = $settings;
        return $this;
    }
}