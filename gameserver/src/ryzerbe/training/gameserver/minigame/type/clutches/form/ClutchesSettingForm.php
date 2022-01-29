<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches\form;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesGameSession;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesMinigame;
use ryzerbe\training\gameserver\session\SessionManager;
use function array_keys;
use function array_search;
use function array_values;
use function is_int;

class ClutchesSettingForm {
    public static function open(Player $player): void{
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        if($session === null) return;
        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof ClutchesGameSession) return;
        $knockBackLevelKey = array_search($gameSession->getKnockBackLevel(), array_values(ClutchesMinigame::KNOCKBACK_LEVELS));
        if(!is_int($knockBackLevelKey)) $knockBackLevelKey = 0;

        $hitTypeKey = array_search($gameSession->getHitType(), array_values(ClutchesMinigame::HIT_TYPES));
        if(!is_int($hitTypeKey)) $hitTypeKey = 0;

        $running = $gameSession->isRunning();
        $form = new CustomForm(function(Player $player, $data) use ($gameSession, $running): void{
            if($data === null){
                $gameSession->setRunning($running);
                return;
            }

            $gameSession->setHitType(array_values(ClutchesMinigame::HIT_TYPES)[$data["hitType"]]);
            $gameSession->setKnockBackLevel(array_values(ClutchesMinigame::KNOCKBACK_LEVELS)[$data["knockBackLevel"]]);
            $gameSession->setSeconds($data["seconds"]);
            $gameSession->reset();
            $gameSession->sendScoreboard();
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
       });

       $form->setTitle(ClutchesMinigame::$PREFIX);
       $form->addDropdown(TextFormat::RED."Hit type", array_keys(ClutchesMinigame::HIT_TYPES), $hitTypeKey, "hitType");
       $form->addDropdown(TextFormat::RED."Knockback level", array_keys(ClutchesMinigame::KNOCKBACK_LEVELS), $knockBackLevelKey, "knockBackLevel");
       $form->addSlider(TextFormat::RED."Seconds", 2, 5, 1, (int)$gameSession->getSeconds(), "seconds");
       $form->sendToPlayer($player);
    }
}