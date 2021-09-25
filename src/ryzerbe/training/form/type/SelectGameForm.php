<?php

namespace ryzerbe\training\form\type;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use baubolp\core\provider\LanguageProvider;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\challenge\Challenge;
use ryzerbe\training\challenge\ChallengeManager;
use ryzerbe\training\form\Form;
use ryzerbe\training\player\TrainingPlayerManager;
use ryzerbe\training\Training;
use function count;
use function json_encode;

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
            $manager = ChallengeManager::getInstance();

            $opponentPlayer = TrainingPlayerManager::getPlayer($extraData["opponent"]);
            if($opponentPlayer === null) return;

            /** @var Challenge $challenge */
            $challenge = $extraData["challenge"];
            if($trainingPlayer->getTeam() !== null) {
                if($opponentPlayer->getTeam() === null) {
                    $opponentPlayer->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-request-invalid",  $opponentPlayer->getPlayer()->getName()));
                    return;
                }
                if(count($challenge->getTeam()->getPlayers()) != count($trainingPlayer->getTeam()->getPlayers())) {
                    $challenge->remove();
                    $opponentPlayer->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-request-invalid",  $opponentPlayer->getPlayer()->getName()));
                    return;
                }
            }

            $pk = new MatchPacket();
            $pk->addData("group", "Training");
            $pk->addData("minigame", $data);
            if($challenge->getTeam() === null && $trainingPlayer->getTeam() === null) {
                $pk->addData("teams", json_encode([
                    "team_1" => [
                        "players" => [$opponentPlayer->getPlayer()->getName()],
                        "data" => ["name" => "Team 1", "color" => "§b"]
                    ],
                    "team_2" => [
                        "players" => [$player->getName()],
                        "data" => ["name" => "Team 2", "color" => "§c"]
                    ]
                ]));
            }else if($challenge->getTeam() !== null && $trainingPlayer->getTeam() !== null) {
                $pk->addData("teams", json_encode([
                    "team_1" => [
                        "players" => $challenge->getTeam()->getPlayers(true),
                        "data" => ["name" => "Team 1", "color" => "§b"]
                    ],
                    "team_2" => [
                        "players" => $trainingPlayer->getTeam()->getPlayers(true),
                        "data" => ["name" => "Team 2", "color" => "§c"]
                    ]
                ])); //todo: team name configurable
            }else {
                $opponentPlayer->getPlayer()->sendMessage("ERROR WITH TEAM SHIT");
                $player->getPlayer()->sendMessage("ERROR WITH TEAM SHIT");
                return;
            }

            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            $manager->removeChallenge($player->getName(), $opponentPlayer->getPlayer()->getName());
            $manager->removeChallenge($opponentPlayer->getPlayer()->getName(), $player->getName());
        });

        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Select Game");
        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::BLUE.TextFormat::BOLD." KitPvP", -1, "", "KitPvP");
        $form->sendToPlayer($player);
    }
}