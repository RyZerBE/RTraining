<?php

namespace ryzerbe\training\gameserver\module\setup;

use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ReflectionException;
use ryzerbe\core\util\customItem\CustomItemManager;
use ryzerbe\core\util\ItemUtils;
use ryzerbe\training\gameserver\module\Module;
use ryzerbe\training\gameserver\module\setup\arena\SetupArena;
use ryzerbe\training\gameserver\module\setup\command\SetupCommand;
use ryzerbe\training\gameserver\module\setup\item\SetupFinishSetupItem;
use ryzerbe\training\gameserver\module\setup\item\SpectatorPositionSetupItem;
use ryzerbe\training\gameserver\module\setup\item\TeamSpawnPositionSetupItem;
use function mt_rand;

class SetupModule extends Module {
    use SingletonTrait;

    private array $items = [];

    private ?SetupArena $arena = null;

    /**
     * @throws ReflectionException
     */
    public function onLoad(): void{
        Server::getInstance()->getCommandMap()->registerAll("training", [
            new SetupCommand()
        ]);
        $items = [
            new TeamSpawnPositionSetupItem(ItemUtils::addItemTag(Item::get(BlockIds::WOOL, mt_rand(0, 15))->setCustomName("§r§aTeam Spawn Positions \n§7[§8Place§7]"), "setup_item", "custom_item")),
            new SpectatorPositionSetupItem(ItemUtils::addItemTag(Item::get(ItemIds::BANNER)->setCustomName("§r§aSpectator Spawn Position \n§7[§8Place§7]"), "setup_item", "custom_item")),
            new SetupFinishSetupItem(ItemUtils::addItemTag(Item::get(BlockIds::CARROTS)->setCustomName("§r§aClick if you are finish with your setup"), "setup_item", "custom_item"))
        ];
        foreach($items as $item){
            CustomItemManager::getInstance()->registerCustomItem($item);
            $this->items[] = $item->getItem();
        }
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }

    public function getArena(): ?SetupArena{
        return $this->arena;
    }

    public function setArena(?SetupArena $arena): void{
        $this->arena = $arena;
    }
}