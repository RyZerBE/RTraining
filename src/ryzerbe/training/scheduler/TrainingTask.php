<?php

namespace ryzerbe\training\scheduler;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use ryzerbe\training\challenge\ChallengeManager;
use ryzerbe\training\player\TrainingPlayerManager;
use function time;

class TrainingTask extends Task {

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick){
        foreach(ChallengeManager::getInstance()->getChallenges() as $challenge) {
            if($challenge->isValid()) continue;
            $challenge->remove();
        }

        foreach(TrainingPlayerManager::getPlayers() as $trainingPlayer) {
            foreach($trainingPlayer->getTeamRequests() as $requester => $time) {
                if($time > time()) continue;
                $trainingPlayer->removeTeamRequest($requester);
            }

            if($trainingPlayer->getPlayer()->getY() < 70)
                $trainingPlayer->getPlayer()->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn()->add(0, 1));
        }
    }
}