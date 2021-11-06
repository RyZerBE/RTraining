<?php

namespace ryzerbe\training\lobby\team;

use baubolp\core\provider\LanguageProvider;
use pocketmine\Player;
use ryzerbe\training\lobby\player\TrainingPlayer;
use ryzerbe\training\lobby\player\TrainingPlayerManager;
use ryzerbe\training\lobby\Training;
use function count;
use function uniqid;

class Team {

    /** @var string */
    private string $id;
    /** @var TrainingPlayer  */
    private TrainingPlayer $creator;

    /** @var Player[]  */
    private array $players = [];

    public function __construct(TrainingPlayer $creator){
        $this->id = uniqid();
        $this->creator = $creator;
    }

    /**
     * @param TrainingPlayer $player
     */
    public function join(TrainingPlayer $player): void{
        $this->addPlayer($player->getPlayer());
        $player->setTeamId($this->getId());
        $player->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("team-joined", $player->getPlayer()->getName(), ["#team" => $this->creator->getPlayer()->getDisplayName()]));
    }

    /**
     * @param TrainingPlayer $player
     */
    public function leave(TrainingPlayer $player): void{
        $this->removePlayer($player->getPlayer());
        $player->setTeamId(null);
        $player->getPlayer()->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("team-leave", $player->getPlayer()->getName(), ["#team" => $this->creator->getPlayer()->getDisplayName()]));
        if(count($this->getPlayers()) <= 1 || $player->getPlayer()->getName() === $this->creator->getPlayer()->getName()) {
            foreach($this->getPlayers() as $teamPlayer) {
                $teamTrainingPlayer = TrainingPlayerManager::getPlayer($teamPlayer);
                if($teamTrainingPlayer === null) continue;

                $teamTrainingPlayer->setTeamId(null);
                $teamPlayer->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-team-drained", $teamPlayer->getName()));
            }
            TeamManager::unregisterTeam($this);
        }
    }

    /**
     * @return string
     */
    public function getId(): string{
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isFull(): bool{
        return count($this->getPlayers()) >= 2;
    }

    /**
     * @return Player[]
     */
    public function getPlayers(bool $names = false): array {
        if($names) {
            $playerNames = [];
            foreach($this->players as $player)$playerNames[] = $player->getName();
            return $playerNames;
        }
        return $this->players;
    }

    /**
     * @param Player $player
     */
    public function addPlayer(Player $player): void {
        $this->players[$player->getName()] = $player;
    }

    /**
     * @param $player
     */
    public function removePlayer($player): void {
        if($player instanceof Player) $player = $player->getName();
        if(!$this->isPlayer($player)) return;
        unset($this->players[$player]);
    }

    /**
     * @param $player
     * @return bool
     */
    public function isPlayer($player): bool {
        if($player instanceof Player) $player = $player->getName();
        return isset($this->players[$player]);
    }

    /**
     * @return TrainingPlayer
     */
    public function getCreator(): TrainingPlayer{
        return $this->creator;
    }
}