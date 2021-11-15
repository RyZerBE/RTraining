<?php

namespace ryzerbe\training\lobby\challenge;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\player\TrainingPlayer;
use ryzerbe\training\lobby\team\Team;
use ryzerbe\training\lobby\Training;
use function time;

class Challenge {

    /** @var Team|null  */
    private ?Team $team;

    /** @var int  */
    private int $createdTime;

    /** @var string  */
    private string $miniGameName;

    private TrainingPlayer $challenger;
    private string $challengedPlayer;

    public function __construct(TrainingPlayer $challenger, string $challengedPlayer, string $miniGameName, ?Team $team){
        $this->challenger = $challenger;
        $this->team = $team;
        $this->createdTime = time();
        $this->challengedPlayer = $challengedPlayer;
        $this->miniGameName = $miniGameName;
    }

    /**
     * @return Team|null
     */
    public function getTeam(): ?Team{
        return $this->team;
    }

    /**
     * @return TrainingPlayer
     */
    public function getChallenger(): TrainingPlayer{
        return $this->challenger;
    }

    /**
     * @return string
     */
    public function getChallengedPlayerName(): string{
        return $this->challengedPlayer;
    }

    /**
     * @return bool
     */
    public function isValid(): bool{
        return (time() - $this->createdTime) < 20;
    }

    /**
     * @return string
     */
    public function getMiniGameName(): string{
        return $this->miniGameName;
    }

    public function remove(): void{
        ChallengeManager::getInstance()->removeChallenge($this->challenger->getPlayer()->getName(), $this->challengedPlayer);
    }

    /**
     * @param TrainingPlayer $trainingPlayer
     * @param TrainingPlayer $opponentPlayer
     */
    public function accept(TrainingPlayer $trainingPlayer, TrainingPlayer $opponentPlayer){
        $manager = ChallengeManager::getInstance();
        $player = $trainingPlayer->getPlayer();
        
        if($trainingPlayer->getTeam() !== null) {
            if($opponentPlayer->getTeam() === null) {
                $opponentPlayer->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-request-invalid",  $opponentPlayer->getPlayer()->getName()));
                return;
            }
            if(count($this->getTeam()->getPlayers()) != count($trainingPlayer->getTeam()->getPlayers())) {
                $this->remove();
                $opponentPlayer->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-request-invalid",  $opponentPlayer->getPlayer()->getName()));
                return;
            }
        }

        $pk = new MatchPacket();
        $pk->addData("group", "Training");
        $pk->addData("minigame", $this->getMiniGameName());
        $pk->addData("kitName", $trainingPlayer->getKit()->getName());
        if($this->getTeam() === null && $trainingPlayer->getTeam() === null) {
            $pk->addData("players", json_encode([$opponentPlayer->getPlayer()->getName(), $player->getName()]));
            $pk->addData("teams", json_encode([
                "team_1" => [
                    "players" => [$opponentPlayer->getPlayer()->getName()],
                    "data" => ["name" => "Team 1", "color" => "§b"]
                ],
                "team_2" => [
                    "players" => [$player->getName()],
                    "data" => ["name" => "Team 2", "color" => "§c"]
                ]
            ]));
        }else if($this->getTeam() !== null && $trainingPlayer->getTeam() !== null) {
            $playerNames = [];

            foreach($opponentPlayer->getTeam()->getPlayers(true) as $playerNameee) {
                $playerNames[] = $playerNameee;
            }
            foreach($this->getTeam()->getPlayers(true) as $playerNameee) {
                $playerNames[] = $playerNameee;
            }
            $pk->addData("players", json_encode($playerNames));
            $pk->addData("teams", json_encode([
                "team_1" => [
                    "players" => $this->getTeam()->getPlayers(true),
                    "data" => ["name" => "Team 1", "color" => "§b"]
                ],
                "team_2" => [
                    "players" => $opponentPlayer->getTeam()->getPlayers(true),
                    "data" => ["name" => "Team 2", "color" => "§c"]
                ]
            ])); //todo: team name configurable
        }else {
            $opponentPlayer->getPlayer()->sendMessage("ERROR WITH TEAM SHIT");
            $trainingPlayer->getPlayer()->sendMessage("ERROR WITH TEAM SHIT");
            return;
        }

        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
        $manager->removeChallenge($player->getName(), $opponentPlayer->getPlayer()->getName());
        $manager->removeChallenge($opponentPlayer->getPlayer()->getName(), $player->getName());
    }
}