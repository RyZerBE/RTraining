<?php

namespace ryzerbe\training\minigame\type\bridger\form;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use ryzerbe\training\minigame\type\bridger\BridgerGameSession;
use ryzerbe\training\minigame\type\bridger\BridgerMinigame;
use ryzerbe\training\session\SessionManager;
use function array_keys;
use function array_map;
use function array_search;
use function array_values;
use function is_int;

class BridgerMinigameConfigurationForm {
    public static function open(Player $player): void {
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof BridgerGameSession) return;

        $distanceKey = array_search($gameSession->getDistance(), array_values(BridgerMinigame::DISTANCE_LIST));
        if(!is_int($distanceKey)) $distanceKey = 0;
        $typeKey = array_search($gameSession->getRotation(), array_values(BridgerMinigame::ROTATION_LIST));
        if(!is_int($typeKey)) $typeKey = 0;

        $form = new CustomForm(function(Player $player, mixed $data) use ($gameSession): void {
            if($data === null) return;

            $gameSession->reset();

            $gameSession->setRotation(array_values(BridgerMinigame::ROTATION_LIST)[$data["rotation"]]);
            $gameSession->setDistance(array_values(BridgerMinigame::DISTANCE_LIST)[$data["distance"]]);
            $gameSession->setGradient($data["gradient"] ?? 0);
            $gameSession->generateGoalPlatform();
            $gameSession->sendScoreboard();
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        });
        $form->setTitle(BridgerMinigame::$PREFIX);
        $form->addStepSlider("§cDistance", array_map(function(string|int $value): string {
            return (string)$value;
        }, array_keys(BridgerMinigame::DISTANCE_LIST)), $distanceKey, "distance");
        $form->addDropdown("§cType", array_keys(BridgerMinigame::ROTATION_LIST), $typeKey, "rotation");
        if($player->isOp()) $form->addInput("§cGradient (Only OP)", "", "" . $gameSession->getGradient(), "gradient");
        $form->sendToPlayer($player);
    }
}