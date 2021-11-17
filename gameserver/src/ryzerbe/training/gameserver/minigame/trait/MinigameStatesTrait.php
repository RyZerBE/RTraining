<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\trait;

trait MinigameStatesTrait {
    private bool $running = false;

    private int $state = -1;

    public function isRunning(): bool{
        return $this->running;
    }

    public function setRunning(bool $running): void{
        $this->running = $running;
    }

    public function getState(): int{
        return $this->state;
    }

    public function setState(int $state): void{
        $this->state = $state;
    }
}