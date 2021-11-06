<?php

namespace ryzerbe\training\gameserver\minigame\trait;

use function microtime;

trait StopWatchTrait {

    /** @var bool[]  */
    private array $running = [];

    /** @var float[]  */
    private array $startTime = [];
    /** @var float[]  */
    private array $stopTime = [];
    
    /** @var float[]  */
    private array $score = [];
    /** @var float[]  */
    private array $topScore = [];

    public function startTimer(string $identifier = "default"): void {
        $this->startTime[$identifier] = microtime(true);
        $this->running[$identifier] = true;
    }

    public function stopTimer(string $identifier = "default"): void {
        $this->stopTime[$identifier] = microtime(true);
        $this->running[$identifier] = false;
    }

    public function isTimerRunning(string $identifier = "default"): bool{
        return $this->running[$identifier] ?? false;
    }

    public function getTimer(string $identifier = "default"): float {
        if(empty($this->startTime[$identifier])) return 0.0;

        return microtime(true) - $this->startTime[$identifier] ?? 0.0;
    }

    public function updateScore(string $identifier = "default"): void {
        $this->score[$identifier] = ($this->stopTime[$identifier] ?? 0.0) - ($this->startTime[$identifier] ?? 0.0);
        if($this->getScore($identifier) < $this->getTopScore($identifier) || $this->getTopScore($identifier) === 0.0) $this->topScore[$identifier] = $this->score[$identifier];
    }

    public function getScore(string $identifier = "default"): float {
        return $this->score[$identifier] ?? 0.0;
    }

    public function getTopScore(string $identifier = "default"): float {
        return $this->topScore[$identifier] ?? 0.0;
    }

    public function resetTimer(string $identifier = "default"): void {
        unset($this->startTime[$identifier]);
        unset($this->stopTime[$identifier]);
        unset($this->running[$identifier]);
    }
}