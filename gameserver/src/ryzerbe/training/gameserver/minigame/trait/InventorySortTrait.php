<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\trait;

use Closure;
use mysqli;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\util\async\AsyncExecutor;
use function array_values;
use function count;
use function json_decode;
use function strtolower;

trait InventorySortTrait {
    private array $items = [];

    /**
     * @param Item[] $items
     */
    public function registerItems(string $minigame, ?string $key, array $items): void {
        foreach($items as $identifier => $item) {
            if($key !== null) {
                $this->items[$minigame][$key][$identifier] = $item;
            } else {
                $this->items[$minigame][$identifier] = $item;
            }
        }
    }

    public function getItems(string $minigame, ?string $key = null): array{
        if($key !== null) {
            return array_values($this->items[$minigame][$key] ?? []);
        }
        return array_values($this->items[$minigame] ?? []);
    }

    public function getItem(string $minigame, string $identifier, ?string $key = null): ?Item {
        if($key !== null) {
            return $this->items[$minigame][$key][$identifier] ?? null;
        }
        return $this->items[$minigame][$identifier] ?? null;
    }

    public function loadInventory(Player $player, string $minigame, ?string $key, ?Closure $closure): void {
        $playername = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($minigame, $playername, $key): ?string {
            $table = strtolower($minigame) . "_inventory_sort";
            if($key === null) {
                $query = $mysqli->query("SELECT inventory FROM $table WHERE playername='$playername'");
            } else {
                $query = $mysqli->query("SELECT inventory FROM $table WHERE playername='$playername' AND identifier='$key'");
            }
            if($query->num_rows <= 0) return null;
            return $query->fetch_assoc()["inventory"];
        }, function(Server $server, ?string $result) use ($player, $minigame, $closure, $key): void {
            if(!$player->isConnected()) return;
            $defaultItems = $this->getItems($minigame, $key);
            $items = [];
            if($result === null || empty($decode = @json_decode($result, true))) {
                $items = $defaultItems;
            } else {
                foreach($decode as $slot => $item) {
                    $item = $this->getItem($minigame, $item, $key);
                    if($item === null) continue;
                    $items[$slot] = $item;
                }
            }
            if(count($defaultItems) !== count($items)) {
                $items = $defaultItems;
            }
            $player->getInventory()->setContents($items);
            if($closure !== null) ($closure)();
        });
    }
}