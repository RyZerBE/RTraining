<?php

namespace ryzerbe\training\challenge;

use pocketmine\Player;
use pocketmine\utils\SingletonTrait;
use ryzerbe\training\player\TrainingPlayer;

class ChallengeManager {
    use SingletonTrait;

    /** @var Challenge[][]  */
    public array $challenges = [];

    /**
     * @param TrainingPlayer $challengedPlayer
     * @param Challenge $challenge
     */
    public function addChallenge(TrainingPlayer $challengedPlayer, Challenge $challenge){
        $this->challenges[$challengedPlayer->getPlayer()->getName()][$challenge->getChallenger()->getPlayer()->getName()] = $challenge;
    }

    /**
     * @param Player|string $challenger
     * @param Player|string $enemy
     * @return Challenge|null
     */
    public function hasChallenged(Player|string $challenger, Player|string $enemy): ?Challenge{
        if($challenger instanceof Player) $challenger = $challenger->getName();
        if($enemy instanceof Player) $enemy = $enemy->getName();

        return $this->challenges[$enemy][$challenger] ?? null;
    }

    /**
     * @param string $challengerName
     * @param string $challengedName
     */
    public function removeChallenge(string $challengerName, string $challengedName){
        unset($this->challenges[$challengedName][$challengerName]);
    }
    /**
     * @return Challenge[]
     */
    public function getChallenges(): array{
        return $this->challenges;
    }

    /**
     * @return Challenge[]
     */
    public function getPlayerChallenges(Player|string $player): array{
        if($player instanceof Player) $player = $player->getName();

        return $this->challenges[$player];
    }
}