<?php

namespace ryzerbe\training\lobby\scheduler;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use ryzerbe\training\lobby\challenge\ChallengeManager;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
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

            if($trainingPlayer->getPlayer()->getY() < 80)
                $trainingPlayer->getPlayer()->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn()->add(0, 1));
        }
    }
}