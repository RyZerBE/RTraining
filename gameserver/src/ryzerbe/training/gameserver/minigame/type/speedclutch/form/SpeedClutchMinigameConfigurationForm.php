<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\speedclutch\form;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use ryzerbe\training\gameserver\minigame\type\speedclutch\SpeedClutchGameSession;
use ryzerbe\training\gameserver\session\SessionManager;
use function intval;
use function strval;

class SpeedClutchMinigameConfigurationForm {
    public static function open(Player $player): void {
        $gameSession = SessionManager::getInstance()->getSessionOfPlayer($player)?->getGameSession();
        if(!$gameSession instanceof SpeedClutchGameSession) return;

        $form = new CustomForm(function(Player $player, mixed $data) use ($gameSession): void{
            if($data === null) return;

            $seedPlayer = $player->getServer()->getPlayer($data["seed"]);
            $seed = intval($data["seed"]);
            if($seedPlayer !== null) {
                $seedPlayerSession = SessionManager::getInstance()->getSessionOfPlayer($seedPlayer);
                if($seedPlayerSession === null) return;
                $seedPlayerGameSession = $seedPlayerSession->getGameSession();
                if($seedPlayerGameSession instanceof SpeedClutchGameSession) {
                    $seed = $seedPlayerGameSession->getSeed();
                }
            }

            if($gameSession->getSeed() !== $seed) {
                $gameSession->resetTimer("default", true);
                $gameSession->setSeed($seed);
                $gameSession->resetBlocks();
                $gameSession->generateMap();
            }

            $gameSession->resetGame();
        });
        $form->setTitle($gameSession->getSettings()->PREFIX);
        $form->addInput("§cSeed (or another playername to get their seed)", "", strval($gameSession->getSeed()), "seed");
        $form->sendToPlayer($player);
    }
}