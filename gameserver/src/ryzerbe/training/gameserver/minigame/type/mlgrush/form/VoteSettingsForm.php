<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\mlgrush\form;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesMinigame;
use ryzerbe\training\gameserver\minigame\type\mlgrush\MLGRushGameSession;
use ryzerbe\training\gameserver\minigame\type\mlgrush\MLGRushMinigame;
use ryzerbe\training\gameserver\session\SessionManager;
use function array_keys;
use function array_map;
use function array_values;
use function intval;

class VoteSettingsForm {
    public static function open(Player $player): void {
        $form = new CustomForm(function(Player $player, mixed $data): void {
            $session = SessionManager::getInstance()->getSessionOfPlayer($player);
            $gameSession = $session?->getGameSession();
            if(!$gameSession instanceof MLGRushGameSession || $gameSession->isRunning()) return;
            $gameSession->addVote();
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
            if($data === null) return;

            $gameSession->vote("points", array_values(MLGRushMinigame::POINTS_LIST)[$data["points"] ?? PHP_INT_MAX]);
            $gameSession->vote("infiniteBlocks", intval($data["infiniteBlocks"] ?? true));
            $gameSession->vote("wallsEnabled", intval($data["wallsEnabled"] ?? false));
        });
        $form->setTitle(ClutchesMinigame::$PREFIX);
        $form->addStepSlider("§cPoints", array_map(function(string|int $value): string {
            return (string)$value;
        }, array_keys(MLGRushMinigame::POINTS_LIST)), 0, "points");
        $form->addToggle("§cInfinite Blocks", true, "infiniteBlocks");
        //$form->addToggle("§cWalls", true, "wallsEnabled");
        $form->sendToPlayer($player);
    }
}