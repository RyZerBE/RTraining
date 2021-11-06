<?php

namespace ryzerbe\training\minigame\type\aimtrainer;

use pocketmine\utils\TextFormat;
use ryzerbe\training\minigame\MinigameSettings;

class AimTrainerSettings extends MinigameSettings {
    public string $PREFIX = TextFormat::GRAY.TextFormat::BOLD."Aim Trainer ".TextFormat::RESET;
    public bool $inventoryTransactions = true;
    public bool $canInteract = true;
    public bool $damage = true;
}