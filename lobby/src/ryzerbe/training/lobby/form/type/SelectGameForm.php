<?php

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\form\Form;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use ryzerbe\training\lobby\Training;

class SelectGameForm extends Form {

    /**
     * @param Player $player
     * @param array $extraData
     * @return void
     */
    public static function open(Player $player, array $extraData = []): void{
        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingPlayer === null) return;

        $form = new SimpleForm(function(Player $player, $data) use ($trainingPlayer, $extraData): void{
            if($data === null) return;

            $entityName = $extraData["opponent"];
            $entity = Server::getInstance()->getPlayerExact($entityName);
            if($entity === null) return;

            $willChallenge = TrainingPlayerManager::getPlayer($entity);
            $challenger = TrainingPlayerManager::getPlayer($player);
            if($willChallenge === null || $challenger === null) return;

            if($willChallenge->getTeam() === null && $challenger->getTeam() !== null){
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-teams-only-challenge-teams", $player->getName()));
                return;
            }

            if($willChallenge->getTeam() !== null && $challenger->getTeam() === null){
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-already-in-team", $player->getName()));
                return;
            }

            if($willChallenge->getTeam() !== null && $challenger->getTeam() !== null){
                $creatorName = $willChallenge->getTeam()->getCreator()->getPlayer()->getName();
                if($creatorName != $entity->getName()){
                    $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-isnt-team-creator", $player->getName(), ["#creator" => $creatorName]));
                    return;
                }
            }

            if(!$willChallenge->getPlayerSettings()->allowChallengeRequests()){
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-challenge-request-disabled", $player->getName()));
                return;
            }
            $willChallenge->challenge($challenger, $data);
        });

        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Select Game");
        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::BLUE.TextFormat::BOLD." KitPvP", -1, "", "KitPvP");
        $form->sendToPlayer($player);
    }
}