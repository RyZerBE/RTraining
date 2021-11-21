<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\minigame;

use mysqli;
use pocketmine\item\Item;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\statssystem\provider\StatsProvider;
use ryzerbe\statssystem\StatsSystem;
use ryzerbe\training\lobby\entity\NPCEntity;
use ryzerbe\training\lobby\inventory\InventorySortManager;
use ryzerbe\training\lobby\queue\Queue;
use ryzerbe\training\lobby\queue\QueueManager;
use function array_filter;
use function array_key_first;
use function explode;

class MinigameManager {
    use SingletonTrait;

    /** @var Minigame[]  */
    private array $minigames = [];

    /**
     * @return Minigame[]
     */
    public function getMinigames(): array{
        return $this->minigames;
    }

    public function registerMinigames(Minigame... $minigames): void {
        foreach($minigames as $minigame) {
            $this->registerMinigame($minigame);
        }
    }

    public function registerMinigame(Minigame $minigame): void {
        $minigameName = $minigame->getName();
        $this->minigames[$minigameName] = $minigame;
        if($minigame->hasQueue() || ($minigame->isBeta() && $minigame->isMultiplayer())) {
            $queue = new Queue($minigameName);
            $queue->setElo($minigame->isElo());
            QueueManager::getInstance()->registerQueue($queue);
        }
        if($minigame->isElo()) {
            AsyncExecutor::submitMySQLAsyncTask(StatsSystem::DATABASE, function(mysqli $mysqli) use ($minigameName): void {
                StatsProvider::createCategory($mysqli, $minigameName, ["elo" => "INT"], ["elo" => 1000]);
            });
        }
        $items = $minigame->getItems();
        if($items !== null){
            $item = $items[array_key_first($items)];
            if($item instanceof Item) {
                InventorySortManager::getInstance()->registerItems($minigameName, null, $items);
            } else {
                foreach($items as $key => $__items) {
                    InventorySortManager::getInstance()->registerItems($minigameName, $key, $__items);
                }
            }
        }
        $npcSettings = $minigame->getNpcSettings();
        if($npcSettings !== null) {
            $npcEntity = new NPCEntity($npcSettings->getLocation(), $npcSettings->getSkin());
            $title = explode(TextFormat::EOL, $npcSettings->getTitle());
            $npcEntity->updateTitle($title[0], $title[1] ?? "");
            $npcEntity->setGroup($npcSettings->getGroup());
            $npcEntity->setQueue($npcSettings->getQueue());
            $npcEntity->spawnToAll();
        }
    }

    public function getMinigame(string $minigame): ?Minigame {
        return $this->minigames[$minigame] ?? null;
    }

    /**
     * @return Minigame[]
     */
    public function getMinigamesByGroup(string $group): array {
        return array_filter($this->getMinigames(), function(Minigame $minigame) use ($group): bool {
            return $minigame->getGroup() === $group;
        });
    }
}