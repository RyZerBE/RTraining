<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\inventory;

use Closure;
use mysqli;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\ItemUtils;
use ryzerbe\training\lobby\Training;
use function array_values;
use function count;
use function json_decode;
use function json_encode;
use function strtolower;

class InventorySortManager {
    use SingletonTrait;

    /** @var InventorySortSession[] */
    private array $sessions = [];

    private array $items = [];

    /**
     * @param Item[] $items
     */
    public function registerItems(string $minigame, array $items): void {
        foreach($items as $identifier => $item) {
            $this->items[$minigame][$identifier] = ItemUtils::addItemTag($item, $identifier, "minigame");
        }
    }

    public function getItems(string $minigame): array{
        return array_values($this->items[$minigame] ?? []);
    }

    public function getItem(string $minigame, string $identifier): ?Item {
        return $this->items[$minigame][$identifier] ?? null;
    }

    public function addSession(InventorySortSession $session): void {
        $this->sessions[$session->getPlayer()->getName()] = $session;
    }

    public function removeSession(InventorySortSession $session): void {
        unset($this->sessions[$session->getPlayer()->getName()]);
    }

    public function getSession(Player $player): ?InventorySortSession {
        return $this->sessions[$player->getName()] ?? null;
    }

    /**
     * @return InventorySortSession[]
     */
    public function getSessions(): array{
        return $this->sessions;
    }

    public function loadSession(Player $player, string $minigame, ?Closure $closure): void {
        $playername = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($minigame, $playername): ?string {
            $table = strtolower($minigame) . "_inventory_sort";
            $query = $mysqli->query("SELECT inventory FROM $table WHERE playername='$playername'");
            if($query->num_rows <= 0) return null;
            return $query->fetch_assoc()["inventory"];
        }, function(Server $server, ?string $result) use ($player, $minigame, $closure): void {
            if(!$player->isConnected()) return;
            $defaultItems = $this->getItems($minigame);
            $items = [];
            if($result === null || empty($decode = @json_decode($result, true))) {
                $items = $defaultItems;
            } else {
                foreach($decode as $slot => $item) {
                    $item = $this->getItem($minigame, $item);
                    if($item === null) continue;
                    $items[$slot] = $item;
                }
            }
            if(count($defaultItems) !== count($items)) {
                $items = $defaultItems;
            }
            $player->getInventory()->setContents($items);
            $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("sneak-to-save", $player->getName()));
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 9999, 2, false));
            $player->setImmobile();
            $this->addSession(new InventorySortSession($player, $minigame));
            if($closure !== null) ($closure)();
        });
    }

    public function saveSession(InventorySortSession $session): void {
        $player = $session->getPlayer();
        $items = [];
        foreach($player->getInventory()->getContents() as $slot => $item) {
            $items[$slot] = ItemUtils::getItemTag($item, "minigame");
        }
        $inventory = json_encode($items);
        $playername = $player->getName();
        $minigame = $session->getMinigame();
        AsyncExecutor::submitMySQLAsyncTask("Training", function(mysqli $mysqli) use ($inventory, $playername, $minigame): void {
            $table = strtolower($minigame) . "_inventory_sort";
            $mysqli->query("INSERT INTO $table (playername, inventory) VALUES ('$playername', '$inventory') ON DUPLICATE KEY UPDATE inventory='$inventory'");
        });
    }
}