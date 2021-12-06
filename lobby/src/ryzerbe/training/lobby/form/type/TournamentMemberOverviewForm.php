<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\lobby\tournament\Tournament;

class TournamentMemberOverviewForm {
    public static function open(Player $player, Tournament $tournament): void {
        $form = new SimpleForm(function(Player $player, mixed $data): void {
            if($data === null) return;
            switch($data) {
                case "leave": {
                    //TODO
                    break;
                }
                case "members": {
                    //TODO
                    break;
                }
            }
        });
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Tournament");
        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Members", 0, "", "members");
        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::RED." Leave Tournament", 0, "", "leave");
        $form->sendToPlayer($player);
    }
}