<?php

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use ryzerbe\training\lobby\team\Team;
use ryzerbe\training\lobby\team\TeamManager;
use ryzerbe\training\lobby\Training;

class TeamInviteProgressForm {
    public static function open(Player $player, array $extraData = []): void{
        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingPlayer === null) return;
        $form = new SimpleForm(function(Player $player, $data) use ($trainingPlayer): void{
            if($data === null) return;

            $requestPlayer = TrainingPlayerManager::getPlayer($data);
            if(!$trainingPlayer->hasTeamRequest($data)) {
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-request-invalid", $player->getName()));
                return;
            }
            if($requestPlayer === null){
                $trainingPlayer->removeTeamRequest($data);
                return;
            }
            if($requestPlayer->getTeam() !== null){
                $trainingPlayer->removeTeamRequest($data);
                return;
            }

            $team = new Team($requestPlayer);

            TeamManager::createTeam($team);
            $requestPlayer->removeTeamRequest($trainingPlayer->getPlayer());
            $trainingPlayer->removeTeamRequest($requestPlayer->getPlayer());
            $requestPlayer->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-team-created", $requestPlayer->getPlayer()->getName()));
            $requestPlayer->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-team-partner-accept", $requestPlayer->getPlayer()->getName(), ["#player" => $player->getName()]));
            $trainingPlayer->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-team-created", $player->getName()));
            $team->join($requestPlayer);
            $team->join($trainingPlayer);
            $player->playSound("random.orb", 5.0, 1.0, [$player]);
        });

        $form->setTitle(TextFormat::BLUE.TextFormat::BOLD."Team requests");
        foreach($trainingPlayer->getTeamRequests() as $teamRequest => $time) {
            $requestPlayer = TrainingPlayerManager::getPlayer($teamRequest);
            if($requestPlayer === null) {
                $trainingPlayer->removeTeamRequest($teamRequest);
                continue;
            }
            if($requestPlayer->getTeam() !== null) {
                $trainingPlayer->removeTeamRequest($teamRequest);
                continue;
            }
            $form->addButton(TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.TextFormat::BOLD.$teamRequest, -1, "", $teamRequest);
        }
        $form->sendToPlayer($player);
    }
}