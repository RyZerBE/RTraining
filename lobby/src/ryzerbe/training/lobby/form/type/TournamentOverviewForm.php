<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\lobby\tournament\TournamentManager;
use function count;

class TournamentOverviewForm {
    public static function open(Player $player): void {
        $form = new SimpleForm(function(Player $player, mixed $data): void {
            if($data === null) return;
            switch($data) {
                case "create": {
                    //TODO: Add check
                    TournamentConfigurationForm::open($player);
                    break;
                }
                case "join": {
                    //TODO
                    break;
                }
                case "invites": {
                    //TODO
                    break;
                }
            }
        });
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Tournaments");
        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Create Tournament", 0, "", "create");
        $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Public Tournaments", 0, "", "join");
        if(count(TournamentManager::getTournamentInvitesByPlayer($player)) > 0) {
            $form->addButton(TextFormat::DARK_GRAY."⇨".TextFormat::GREEN." Invites", 0, "", "invites");
        }
        $form->sendToPlayer($player);
    }
}