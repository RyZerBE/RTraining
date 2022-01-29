<?php

namespace ryzerbe\training\gameserver\util;


class Countdown {
    public const START = 0;
    public const END = 1;

    private int $countdown;
    private int $originCountdown;
    private int $state;

    public function __construct(int $countdown, int $state = self::START){
        $this->countdown = $countdown;
        $this->state = $state;
        $this->originCountdown = $countdown;
    }

    public function getState(): int{
        return $this->state;
    }

    public function getCountdown(): int{
        return $this->countdown;
    }

    public function setCountdown(int $countdown): void{
        $this->countdown = $countdown;
    }

    public function resetCountdown(?int $countdown = null): void {
        $this->countdown = $countdown ?? $this->originCountdown;
    }

    public function setOriginCountdown(int $originCountdown): void{
        $this->originCountdown = $originCountdown;
    }

    public function hasFinished(): bool {
        return $this->countdown < 1;
    }

    public function tick(): void {
        $this->countdown--;
    }

    public function tryForceStart(): void{
        if($this->countdown < 5) return;
        $this->countdown = 5;
    }
}