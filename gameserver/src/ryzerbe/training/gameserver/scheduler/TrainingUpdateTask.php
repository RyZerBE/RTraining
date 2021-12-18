<?php

namespace ryzerbe\training\gameserver\scheduler;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\util\TaskUtils;
use ryzerbe\training\gameserver\game\match\MatchQueue;
use ryzerbe\training\gameserver\game\spectate\SpectateQueue;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\session\TournamentSession;
use ryzerbe\training\gameserver\Training;
use ryzerbe\training\gameserver\util\WaitingQueue;
use function array_filter;
use function implode;
use function json_encode;
use function str_repeat;
use function time;

class TrainingUpdateTask extends Task {
    private int $points = 0;

    public function onRun(int $currentTick): void{
        foreach(MinigameManager::getMinigames() as $minigame) {
            $minigame->tick($currentTick);
        }
        foreach(array_filter(SessionManager::getInstance()->getSessions(), function(Session $session): bool {
            return $session instanceof TournamentSession;
        }) as $session) $session->onUpdate($currentTick);

        //We do not need to check every tick if the request is valid and can be proceeded
        if($currentTick % 10 === 0) {
            foreach(MatchQueue::getRequests() as $matchRequest) {
                if(!$matchRequest->isValid()) {
                    MatchQueue::removeQueue($matchRequest);
                    continue;
                }
                if($matchRequest->progress()) {
                    $matchRequest->accept();
                }
            }

            foreach(SpectateQueue::getQueue() as $request) {
                if(!$request->isValid()) {
                    SpectateQueue::removeRequest($request);
                    continue;
                }
                if($request->progress()) {
                    $request->accept();
                }
            }
        }

        if(($currentTick % TaskUtils::secondsToTicks(5)) === 0) {
            $pk = new MatchPacket();
            $matches = [];
            foreach(SessionManager::getInstance()->getSessions() as $session) {
                $gameSession = $session->getGameSession();
                if($gameSession === null) continue;
                if(isset($matches[$gameSession->getSession()->getUniqueId()])) continue;
                $matches[$gameSession->getSession()->getUniqueId()] = [
                    "teams" => $session->getTeams(true),
                    "minigame" => $gameSession->getSession()->getMinigame()->getName(),
                    "players" => $session->getOnlinePlayers(true)
                ];
            }
            $pk->addData("matches", json_encode($matches));
            $pk->addData("group", "TrainingLobby");
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
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
                Server::getInstance()->dispatchCommand($player, "leave");
                continue;
            }

            $player->sendTitle(TextFormat::RED."Searching Session", str_repeat(TextFormat::GOLD."▪ ", $this->points).((3 - $this->points) > 0 ? str_repeat(TextFormat::DARK_GRAY."▪ ", (3 - $this->points)) : ""), 0, 20, 0);
        }

        if($this->points >= 3){
            $this->points = 0;
        }
    }
}