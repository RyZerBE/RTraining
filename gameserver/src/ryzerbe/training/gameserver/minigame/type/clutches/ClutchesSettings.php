<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches;

use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\MinigameSettings;

class ClutchesSettings extends MinigameSettings {
    public string $PREFIX = TextFormat::RED.TextFormat::BOLD."Clutches ".TextFormat::RESET;

    public bool $canPlace = true;
    public bool $inventoryTransactions = true;
    public bool $damage = true;
    public int $deathHeight = 15;
}