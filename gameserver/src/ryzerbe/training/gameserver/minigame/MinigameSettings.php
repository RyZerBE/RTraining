<?php

namespace ryzerbe\training\gameserver\minigame;

use pocketmine\Player;
use ryzerbe\training\gameserver\Training;

abstract class MinigameSettings {
    public string $PREFIX = Training::PREFIX;
    public int $gamemode = Player::ADVENTURE;
    public int $maxPlayers = 1;
    public int $deathHeight = 0;
    public bool $hunger = false;
    public bool $damage = false;
    public bool $pvp = false;
    public bool $itemDrop = false;
    public bool $itemPickup = false;
    public bool $canPlace = false;
    public bool $canBreak = false;
    public bool $onlyPlacedBreak = false;
    public bool $canInteract = false;
    public bool $inventoryTransactions = false;
    public bool $elo = false;
    public array $breakList = [];
}