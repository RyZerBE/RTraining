<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\hitblockclutch;

use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\MinigameSettings;

class HitBlockClutchSettings extends MinigameSettings {
    public string $PREFIX = TextFormat::DARK_AQUA.TextFormat::BOLD."HitBlockClutch ".TextFormat::RESET;

    public bool $canPlace = true;
}