<?php

namespace ryzerbe\training\minigame\type\mlgrush;

use pocketmine\utils\TextFormat;
use ryzerbe\training\minigame\MinigameSettings;

class MLGRushSettings extends MinigameSettings {

    /** @var bool  */
    public bool $canPlace = true;
    /** @var bool  */
    public bool $onlyPlacedBreak = true;
    /** @var bool  */
    public bool $canBreak = true;
    /** @var bool  */
    public bool $pvp = true;
    /** @var bool  */
    public bool $canInteract = true;
    /** @var bool  */
    public bool $damage = true;
    /** @var int  */
    public int $maxPlayers = 8;
    /** @var bool  */
    public bool $inventoryTransactions = true;
    /** @var string  */
    public string $PREFIX = TextFormat::LIGHT_PURPLE.TextFormat::BOLD."M".TextFormat::WHITE."L".TextFormat::LIGHT_PURPLE."GRush ".TextFormat::RESET;
}