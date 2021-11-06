<?php

namespace ryzerbe\training\lobby\kit;

use pocketmine\Player;

class Kit {
    /** @var string  */
    private string $name;
    /** @var array  */
    private array $items, $armor;

    /**
     * Kit constructor.
     * @param string $name
     * @param array $items
     * @param array $armor
     */
    public function __construct(string $name, array $items, array $armor){
        $this->name = $name;
        $this->items = $items;
        $this->armor = $armor;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return array
     */
    public function getArmor(): array{
        return $this->armor;
    }

    /**
     * @return array
     */
    public function getItems(): array{
        return $this->items;
    }

    /**
     * @param Player $player
     */
    public function givePlayer(Player $player){
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getArmorInventory()->setContents($this->getArmor());
        $player->getInventory()->setContents($this->getItems());
    }
}