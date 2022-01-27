<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\speedclutch;

use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\MinigameSettings;

class SpeedClutchSettings extends MinigameSettings {
    public string $PREFIX = TextFormat::DARK_AQUA.TextFormat::BOLD."SpeedClutch ".TextFormat::RESET;

    public bool $canPlace = true;
    public int $deathHeight = 20;
}