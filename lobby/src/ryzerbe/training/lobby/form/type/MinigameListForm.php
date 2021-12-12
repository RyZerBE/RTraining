<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\form\element\Button;
use ryzerbe\training\lobby\Training;
use function json_encode;
use function strtolower;

class MinigameListForm {
    /**
     * @param Button[] $buttons
     */
    public static function open(Player $player, string $title, array $buttons): void {
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null || $data === "soon") return;

            $form = new SimpleForm(function(Player $player, $data): void{
                if($data === null) return;
                $pk = new MatchPacket();
                $pk->addData("group", "Training");
                $pk->addData("minigame", $data);
                $pk->addData("players", json_encode([$player->getName()]));
                CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-creating-session", $player, ["#minigame" => $data]));
            });
            $form->setContent(LanguageProvider::getMessageContainer(strtolower($data)."-guide", $player));
            $form->addButton(TextFormat::GREEN."Create session", -1, "", $data);
            $form->sendToPlayer($player);
        });
        $form->setTitle($title);
        foreach($buttons as $button) {
            $form->addButton($button->getText(), $button->getImageType(), $button->getImagePath(), $button->getLabel());
        }
        $form->sendToPlayer($player);
    }
}