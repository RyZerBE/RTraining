<?php

namespace ryzerbe\training\gameserver\scheduler;

use BauboLP\Cloud\CloudBridge;
use baubolp\core\provider\LanguageProvider;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\game\match\MatchQueue;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\Training;
use ryzerbe\training\gameserver\util\WaitingQueue;
use function str_repeat;
use function time;

class TrainingUpdateTask extends Task {
    private int $points = 0;

    public function onRun(int $currentTick): void{
        foreach(MinigameManager::getMinigames() as $minigame) {
            $minigame->tick($currentTick);
        }

        foreach(MatchQueue::getRequests() as $matchRequest) {
            if(!$matchRequest->isValid()) {
                MatchQueue::removeQueue($matchRequest);
                continue;
            }

            if($matchRequest->progress()) {
                $matchRequest->accept();
            }
        }

        if($currentTick % 20 !== 0) return;
        $this->points++;

        foreach(WaitingQueue::getQueue() as $playerName => $time) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if($player === null || $time === null) {
                WaitingQueue::removePlayer($playerName);
                continue;
            }

            if(SessionManager::getInstance()->getSessionOfPlayer($player) !== null) {
                WaitingQueue::removePlayer($player);
                $player->removeAllEffects();
                continue;
            }

            if(time() > $time) {
                WaitingQueue::removePlayer($player);
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-no-session-found", $playerName));
                CloudBridge::getCloudProvider()->transferPlayer([$player->getName()], "challenge");
                return;
            }

            $player->sendTitle(TextFormat::RED."Searching Session", str_repeat(TextFormat::GOLD."▪ ", $this->points).((3 - $this->points) > 0 ? str_repeat(TextFormat::DARK_GRAY."▪ ", (3 - $this->points)) : ""));
            if($this->points >= 3) $this->points = 0;
        }
    }
}