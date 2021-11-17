<?php

namespace ryzerbe\training\gameserver\minigame\type\kitpvp;

use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\trait\BlockStorageTrait;
use ryzerbe\training\gameserver\minigame\trait\MinigameStatesTrait;
use ryzerbe\training\gameserver\minigame\type\kitpvp\kits\Kit;
use ryzerbe\training\gameserver\minigame\type\kitpvp\kits\KitManager;
use ryzerbe\training\gameserver\util\Countdown;
use ryzerbe\training\gameserver\util\ScoreboardUtils;

class KitPvPGameSession extends GameSession {
    use BlockStorageTrait;
    use MinigameStatesTrait;

    private ?Countdown $countdown;
    public int $tick = 0;
    public ?Kit $kit = null;

    public function loadPlayerKits(): void{
        $session = $this->getSession();
        if($this->getKit() === null) {
            foreach($session->getOnlinePlayers() as $player) {
                KitManager::getInstance()->loadPlayerKit($player);
            }
        }else {
            foreach($session->getOnlinePlayers() as $player) {
                KitManager::getInstance()->loadKitForPlayer($player, $this->getKit()->getName());
            }
        }
    }

    public function getKit(): ?Kit{
        return $this->kit;
    }

    public function setKit(?Kit $kit): void{
        $this->kit = $kit;
    }

    public function getCountdown(): ?Countdown{
        return $this->countdown;
    }

    public function stopCountdown(): void{
        $this->countdown = null;
    }

    public function startCountdown(int $seconds, int $state){
        $this->countdown = new Countdown($seconds, $state);
    }

    public function sendScoreboard(): void{
        foreach($this->getSession()->getOnlinePlayers() as $player) {
            ScoreboardUtils::rmScoreboard($player, "training");
            ScoreboardUtils::createScoreboard($player, $this->getSession()->getMinigame()->getSettings()->PREFIX, "training");
            ScoreboardUtils::setScoreboardEntry($player, 0, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, 1, TextFormat::GRAY."○ Kit", "training");
            ScoreboardUtils::setScoreboardEntry($player, 2, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.(KitManager::getInstance()->playerKit[$player->getName()] ?? "???"), "training");
            ScoreboardUtils::setScoreboardEntry($player, 3, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, 4, TextFormat::GRAY."○ Map", "training");
            ScoreboardUtils::setScoreboardEntry($player, 5, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getSession()->getMinigame()->getMap()->getGameMap()->getMapName(), "training");
            ScoreboardUtils::setScoreboardEntry($player, 6, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, 7, TextFormat::WHITE."⇨ ".TextFormat::AQUA."ryzer.be", "training");
        }
    }
}