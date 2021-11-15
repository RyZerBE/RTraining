<?php

namespace ryzerbe\training\lobby\scheduler;

use pocketmine\scheduler\Task;
use ryzerbe\training\lobby\challenge\ChallengeManager;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use ryzerbe\training\lobby\queue\QueueManager;
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
        }

        foreach(QueueManager::getInstance()->getQueues() as $queue) {
            $queue->update();
        }
    }
}