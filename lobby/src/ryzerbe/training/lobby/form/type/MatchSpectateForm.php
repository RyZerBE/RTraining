<?php

namespace ryzerbe\training\lobby\form\type;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\minigame\MinigameManager;
use ryzerbe\training\lobby\spectate\MatchSpectate;
use ryzerbe\training\lobby\spectate\MatchSpectateManager;
use ryzerbe\training\lobby\Training;
use function count;
use function implode;
use function json_encode;
use function str_replace;

class MatchSpectateForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function onOpen(Player $player, array $extraData = []): void{
        if(!isset($extraData["matches"])) {
            $buttons = [];
            foreach(MatchSpectateManager::getInstance()->getMatches() as $matchSpectate) {
                $buttons[$matchSpectate->getMiniGame()][] = $matchSpectate;
            }
            $form = new SimpleForm(function(Player $player, $data) use ($buttons): void{
                if($data === null) return;

                MatchSpectateForm::onOpen($player, ["matches" => $buttons[$data] ?? []]);
            });

            foreach(MinigameManager::getInstance()->getMinigames() as $minigame) {
                $form->addButton(TextFormat::GRAY."⇨ ".TextFormat::GREEN.TextFormat::BOLD.$minigame->getName()."\n".TextFormat::YELLOW.count($buttons[$minigame->getName()] ?? [])." Matches", -1, "", $minigame->getName());
            }
            $form->setTitle(TextFormat::BLUE."Spectate");
            $form->sendToPlayer($player);
            return;
        }

        $matches = $extraData["matches"] ?? null;
        if($matches === null) return;
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;

            $pk = new MatchPacket();
            $pk->addData("group", "Training");
            $pk->addData("spectate", $data);
            $pk->addData("players", json_encode([$player->getName()]));
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("match-request-spectate", $player->getName()));
        });
        /** @var MatchSpectate $match */
        foreach($matches as $match) {
            $form->addButton(str_replace("Team", "", implode(TextFormat::WHITE.TextFormat::BOLD." VS ".TextFormat::RESET, $match->getTeams()))."\n".TextFormat::YELLOW.count($match->getPlayerNames())." Players", -1, "", $match->getPlayerNames()[0] ?? "");
        }
        $form->setTitle(TextFormat::BLUE."Spectate");
        $form->sendToPlayer($player);
    }
}