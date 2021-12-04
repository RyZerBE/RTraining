<?php

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use function implode;

class TeamOverviewForm {
    public static function open(Player $player, array $extraData = []): void{
        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingPlayer === null) return;
        $form = new SimpleForm(function(Player $player, $data) use ($trainingPlayer): void{
            if($data === null) return;

            switch($data) {
                case "settings":
                    TeamSettingsForm::open($player);
                    break;
                case "leave":
                    $trainingPlayer->getTeam()?->leave($trainingPlayer);
                    break;
            }
        });

        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Team manager");
        $form->setContent(LanguageProvider::getMessageContainer("team-info", $player->getName(), ["#players" => implode(", ", $trainingPlayer->getTeam()->getPlayers(true))]));
        if($trainingPlayer->getTeam()->getCreator()->getPlayer()->getName() === $player->getName())
        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::YELLOW.TextFormat::BOLD." Settings ", -1, "", "settings");
        $form->addButton(TextFormat::RED.TextFormat::BOLD."✘ LEAVE", -1, "", "leave");
        $form->sendToPlayer($player);
    }
}