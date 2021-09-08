<?php

namespace ryzerbe\training\form\type;

use baubolp\core\provider\LanguageProvider;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\form\Form;
use ryzerbe\training\player\TrainingPlayerManager;
use ryzerbe\training\Training;

class TeamRequestForm extends Form {
    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $willInvite = $extraData["playerName"];
        $form = new SimpleForm(function(Player $player, $data) use ($willInvite): void{
            if($data === null) return;

            $trainingEntity = TrainingPlayerManager::getPlayer($willInvite);
            $trainingPlayer = TrainingPlayerManager::getPlayer($player);

            if($trainingEntity === null) return;
            if($trainingPlayer === null) return;

            if($trainingPlayer->getTeam() !== null) {
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-already-in-team", $player->getName()));
                return;
            }

            if($data === "invite") {
                $trainingEntity->addTeamRequest($player->getName());
                $trainingEntity->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-got-team-request", $trainingEntity->getPlayer()->getName(), ["#player" => $player->getName()]));
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-player-invited-team", $player->getName(), ["#player" => $willInvite]));
                $player->playSound("note.bass", 5.0, 3.0, [$player]);
            }
        });

        $form->setTitle(TextFormat::BLUE.TextFormat::BOLD."Really invite?");
        $form->setContent(LanguageProvider::getMessageContainer("training-really-team-invite", $player->getName(), ["#player" => $willInvite]));
        $form->addButton(TextFormat::GREEN.TextFormat::BOLD."✔ Invite ".$willInvite, -1, "", "invite");
        $form->addButton(TextFormat::RED.TextFormat::BOLD."✘ RATHER NOT", -1, "", "close");
        $form->sendToPlayer($player);
    }
}