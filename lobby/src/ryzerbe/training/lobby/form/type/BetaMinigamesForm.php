<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\lobby\minigame\MinigameManager;
use ryzerbe\training\lobby\queue\QueueManager;
use function json_encode;

class BetaMinigamesForm{
    public static function open(Player $player): void {
        $form = new SimpleForm(function(Player $player, mixed $data): void {
            if($data === null) return;
            $minigame = MinigameManager::getInstance()->getMinigame($data);
            if($minigame === null) return;

            if($minigame->isMultiplayer()) {
                QueueManager::getInstance()->getQueue($minigame->getName())?->addPlayer($player);
            } else {
                $pk = new MatchPacket();
                $pk->addData("group", "Training");
                $pk->addData("minigame", $minigame->getName());
                $pk->addData("players", json_encode([$player->getName()]));
                CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            }
        });
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Beta Minigames");
        foreach(MinigameManager::getInstance()->getMinigames() as $minigame) {
            if(!$minigame->isBeta()) continue;
            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::BLUE.TextFormat::BOLD." ".$minigame->getName(), -1, "", $minigame->getName());
        }
        $form->sendToPlayer($player);
    }
}