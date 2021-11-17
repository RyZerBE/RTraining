<?php

namespace ryzerbe\training\lobby\queue;

use pocketmine\utils\SingletonTrait;

class QueueManager {
    use SingletonTrait;

    /** @var Queue[]  */
    private array $queues = [];

    /**
     * @return Queue[]
     */
    public function getQueues(): array{
        return $this->queues;
    }

    public function registerQueue(Queue $queue): void {
        $this->queues[$queue->getMinigame()] = $queue;
    }

    public function registerQueues(Queue... $queues): void {
        foreach($queues as $queue) $this->registerQueue($queue);
    }

    public function getQueue(string $minigame): ?Queue {
        return $this->queues[$minigame] ?? null;
    }
}