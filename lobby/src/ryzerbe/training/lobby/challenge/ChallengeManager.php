<?php

namespace ryzerbe\training\lobby\challenge;

use pocketmine\Player;
use pocketmine\utils\SingletonTrait;
use ryzerbe\training\lobby\player\TrainingPlayer;

class ChallengeManager {
    use SingletonTrait;

    /** @var Challenge[][]  */
    public array $challenges = [];

    public function addChallenge(TrainingPlayer $challengedPlayer, Challenge $challenge){
        $this->challenges[$challengedPlayer->getPlayer()->getName()][$challenge->getChallenger()->getPlayer()->getName()] = $challenge;
    }

    public function hasChallenged(Player|string $challenger, Player|string $enemy, ?string $minigame = null): ?Challenge{
        if($challenger instanceof Player) $challenger = $challenger->getName();
        if($enemy instanceof Player) $enemy = $enemy->getName();
        $challenge = $this->challenges[$enemy][$challenger] ?? null;
        if($challenge === null) return null;
        if($minigame !== null){
            if($challenge->getMiniGameName() === $minigame) {
                return $challenge;
            }
            return null;
        }
        return $challenge;
    }

    public function removeChallenge(string $challengerName, string $challengedName){
        unset($this->challenges[$challengedName][$challengerName]);
    }

    public function getChallenges(): array{
        return $this->challenges;
    }

    /**
     * @return Challenge[]
     */
    public function getPlayerChallenges(Player|string $player): array{
        if($player instanceof Player) $player = $player->getName();
        return $this->challenges[$player] ?? [];
    }
}