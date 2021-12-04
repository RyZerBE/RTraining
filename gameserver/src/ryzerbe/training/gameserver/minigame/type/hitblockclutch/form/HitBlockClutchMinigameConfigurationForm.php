<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\hitblockclutch\form;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\HitBlockClutchGameSession;
use ryzerbe\training\gameserver\session\SessionManager;
use function intval;
use function strval;

class HitBlockClutchMinigameConfigurationForm {
    public static function open(Player $player): void {
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof HitBlockClutchGameSession) return;

        $form = new CustomForm(function(Player $player, mixed $data) use ($gameSession): void{
            if($data === null) return;

            $seed = intval($data["seed"]);
            if($gameSession->getSeed() !== $seed) {
                $gameSession->resetTimer("default", true);
                $gameSession->setSeed($seed);
                $gameSession->resetBlocks();
                $gameSession->generateMap();
            }

            $gameSession->resetGame();
        });
        $form->setTitle($gameSession->getSettings()->PREFIX);
        $form->addInput("§cSeed", "", strval($gameSession->getSeed()), "seed");
        $form->sendToPlayer($player);
    }
}