<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches;

use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\MinigameSettings;
use ryzerbe\training\gameserver\minigame\type\clutches\entity\ClutchesEntity;

class ClutchesSettings extends MinigameSettings {

    // HITS \\
    public const ONE_HIT = 1;
    public const DOUBLE_HIT = 2;
    public const TRIPLE_HIT = 3;
    public const QUADRUPLE_HIT = 4;

    // KnockBackLevels \\
    public const EASY = 0.7;
    public const NORMAL = 1.0;
    public const HARD = 1.3;

    public bool $canPlace = true;
    public bool $inventoryTransactions = true;
    public bool $damage = true;

    public ?ClutchesEntity $entity = null;
    public float $seconds = 5.0;
    public int $hit = self::ONE_HIT;
    public float $knockBackLevel = self::EASY;
    public string $PREFIX = TextFormat::RED.TextFormat::BOLD."Clutches ".TextFormat::RESET;

    public int $deathHeight = 15;
}