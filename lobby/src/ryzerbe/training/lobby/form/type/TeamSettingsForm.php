<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\form\type;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\MySQLProvider;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use function strlen;

class TeamSettingsForm {
    public static function open(Player $player): void {
        $trainingPlayer = TrainingPlayerManager::getPlayer($player);
        if($trainingPlayer === null) return;
        $team = $trainingPlayer->getTeam();

        $form = new CustomForm(function(Player $player, mixed $data) use ($team): void {
            if($data === null) return;

            if(!MySQLProvider::checkInsert($data["name"]) || strlen($data["name"]) > 16) {
                $player->playSound("block.false_permissions", 5.0, 1.0, [$player]);
                return;
            }
            $team->setName($data["name"]);

            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        });
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Team");
        $form->addInput("§cName", "", $team->getName(), "name");
        $form->sendToPlayer($player);
    }
}