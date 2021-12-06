<?php

namespace ryzerbe\training\gameserver\minigame\type\bridger;

use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\MinigameSettings;

class BridgerSettings extends MinigameSettings {
    public string $PREFIX = TextFormat::YELLOW.TextFormat::BOLD."Bridger ".TextFormat::RESET;
    public bool $canPlace = true;
    public bool $inventoryTransactions = true;
    public int $deathHeight = 30;
}