<?php

namespace ryzerbe\training\gameserver\minigame\type\kitpvp;

use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\MinigameSettings;

class KitPvPSettings extends MinigameSettings {
    public string $PREFIX = TextFormat::DARK_AQUA.TextFormat::BOLD."KitPvP ".TextFormat::RESET;
    public int $maxPlayers = 8;
    public bool $damage = true;
    public bool $pvp = true;
    public bool $itemDrop = true;
    public bool $canPlace = true;
    public bool $canBreak = true;
    public bool $onlyPlacedBreak = true;
    public bool $hunger = true;
    public bool $inventoryTransactions = true;
    public bool $itemPickup = true;
    public bool $canInteract = true;

}