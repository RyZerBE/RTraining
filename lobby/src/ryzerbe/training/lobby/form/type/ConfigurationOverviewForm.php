<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\minigame\MinigameManager;
use ryzerbe\training\lobby\player\TrainingPlayerManager;

class ConfigurationOverviewForm {
    public static function open(Player $player): void {
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;
            $trainingPlayer = TrainingPlayerManager::getPlayer($player);
            if($trainingPlayer === null) return;
            switch($data) {
                case "Lobby": {
                    LobbySettingsForm::open($player);
                    break;
                }
                default: {
                    $minigame = MinigameManager::getInstance()->getMinigame($data);
                    if($minigame === null) break;
                    ($minigame->getSettings())($player);
                    break;
                }
            }
        });
        $form->setContent(LanguageProvider::getMessageContainer("training-configuration-select-game", $player->getName()));
        $form->setTitle(TextFormat::AQUA.TextFormat::BOLD."Settings");
        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::YELLOW.TextFormat::BOLD." Lobby", -1, "", "Lobby");
        foreach(MinigameManager::getInstance()->getMinigames() as $minigame) {
            $settings = $minigame->getSettings();
            if($settings === null) continue;
            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::BLUE.TextFormat::BOLD." ".$minigame->getName(), -1, "", $minigame->getName());
        }
        $form->sendToPlayer($player);
    }
}