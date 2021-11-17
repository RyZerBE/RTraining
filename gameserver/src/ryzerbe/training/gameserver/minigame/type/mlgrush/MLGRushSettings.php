<?php

namespace ryzerbe\training\gameserver\minigame\type\mlgrush;

use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\MinigameSettings;

class MLGRushSettings extends MinigameSettings {
    public string $PREFIX = TextFormat::LIGHT_PURPLE.TextFormat::BOLD."M".TextFormat::WHITE."L".TextFormat::LIGHT_PURPLE."GRush ".TextFormat::RESET;
    public int $maxPlayers = PHP_INT_MAX;
    public bool $canPlace = true;
    public bool $onlyPlacedBreak = true;
    public bool $canBreak = true;
    public bool $pvp = true;
    public bool $canInteract = true;
    public bool $damage = true;
    public bool $inventoryTransactions = true;
    public bool $itemPickup = true;
}