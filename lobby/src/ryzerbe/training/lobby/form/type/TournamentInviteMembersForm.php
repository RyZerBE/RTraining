<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\lobby\tournament\Tournament;

class TournamentInviteMembersForm {
    public static function open(Player $player, Tournament $tournament): void {
        $form = new CustomForm(function(Player $player, mixed $data): void {
            if($data === null) return;
        });
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Tournament");
        $form->addInput(TextFormat::RED."Invite Player", "", "", "player");
        $form->sendToPlayer($player);
    }
}