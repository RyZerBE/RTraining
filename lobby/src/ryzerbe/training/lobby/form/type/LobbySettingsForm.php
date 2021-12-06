<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\player\TrainingPlayerManager;

class LobbySettingsForm {
    public static function open(Player $player): void {
        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingPlayer === null) return;
        $form = new CustomForm(function(Player $player, $data) use ($trainingPlayer): void{
            if($data === null) return;
            $trainingPlayer->getPlayerSettings()->setTeamRequests($data["team_requests"]);
            $trainingPlayer->getPlayerSettings()->setChallengeRequests($data["match_requests"]);
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        });
        $form->addToggle(LanguageProvider::getMessageContainer("training-team-request-setting", $player->getName()), $trainingPlayer->getPlayerSettings()->allowTeamRequests(), "team_requests");
        $form->addToggle(LanguageProvider::getMessageContainer("training-match-request-setting", $player->getName()), $trainingPlayer->getPlayerSettings()->allowTeamRequests(), "match_requests");
        $form->sendToPlayer($player);
    }
}