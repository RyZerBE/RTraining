<?php

namespace ryzerbe\training\minigame\type\clutches\form;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\minigame\type\clutches\ClutchesGameSession;
use ryzerbe\training\minigame\type\clutches\ClutchesMinigame;
use ryzerbe\training\minigame\type\clutches\ClutchesSettings;
use ryzerbe\training\session\SessionManager;

class ClutchesSettingForm {
    public static function open(Player $player): void{
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        if($session === null) return;
        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return;

        $hits = ["One hit", "Double hit", "Triple hit", "Quadruple hit"];
        $knockbackLevels = ["Easy", "Normal", "Hard"];
        $form = new CustomForm(function(Player $player, $data) use ($gameSession, $hits, $knockbackLevels): void{
            if($data === null) return;

            $knockBackIdentifier = $knockbackLevels[$data["knockBackLevel"]] ?? "Normal";
            $knockBackLevel = match($knockBackIdentifier) {
                "Easy" => ClutchesSettings::EASY,
                "Normal" => ClutchesSettings::NORMAL,
                "Hard" => ClutchesSettings::HARD,
            };

            $hitOption = $data["hit"] ?? ClutchesSettings::ONE_HIT;
            $seconds = $data["seconds"] ?? 5;
            $gameSession->getSettings()->knockBackLevel = $knockBackLevel;
            $gameSession->getSettings()->hit = $hitOption+1;
            $gameSession->getSettings()->seconds = $seconds;
            $gameSession->sendScoreboard();
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
       });

       $form->setTitle(ClutchesMinigame::$PREFIX);
       $form->addDropdown(TextFormat::RED."Hit option", $hits,$gameSession->getSettings()->hit -1, "hit");
       $form->addDropdown(TextFormat::RED."Level of knockback", $knockbackLevels, 1, "knockBackLevel");
       $form->addSlider(TextFormat::RED."Seconds", 2, 5, 1, (int)$gameSession->getSettings()->seconds, "seconds");
       $form->sendToPlayer($player);
    }
}