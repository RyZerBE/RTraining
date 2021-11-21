<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type\minigame;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\lobby\inventory\InventorySortManager;

class MLGRushSettingsForm {
    public static function open(Player $player): void{
        $form = new SimpleForm(function(Player $player, mixed $data): void {
            if($data === null) return;
            switch($data) {
                case "sort": {
                    InventorySortManager::getInstance()->loadSession($player, "MLGRush", null, null);
                    break;
                }
            }
        });
        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Sort Inventory", 0, "", "sort");
        $form->sendToPlayer($player);
    }
}