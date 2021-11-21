<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\inventory;

use Closure;
use pocketmine\Player;

class InventorySortSession {
    private Player $player;
    private string $minigame;
    private ?string $key;

    private ?Closure $closure = null;

    public function __construct(Player $player, string $minigame, ?string $key){
        $this->player = $player;
        $this->minigame = $minigame;
        $this->key = $key;
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getMinigame(): string{
        return $this->minigame;
    }

    public function getKey(): ?string{
        return $this->key;
    }

    public function setClosure(?Closure $closure): void{
        $this->closure = $closure;
    }

    public function onSessionStart(): void {

    }

    public function onSessionStop(): void {
        $player = $this->getPlayer();
        $minigame = $this->getMinigame();

        ($this->closure)();
    }
}