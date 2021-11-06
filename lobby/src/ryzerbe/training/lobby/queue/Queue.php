<?php

namespace ryzerbe\training\lobby\queue;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use pocketmine\Player;
use function array_shift;
use function count;
use function json_encode;
use function strval;

class Queue {
    private string $minigame;

    /** @var Player[] */
    private array $players = [];

    private int $minPlayers = 2;
    private bool $elo = false;

    public function __construct(string $minigame){
        $this->minigame = $minigame;
    }

    public function getMinigame(): string{
        return $this->minigame;
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array{
        return $this->players;
    }

    public function addPlayer(Player $player): void {
        $this->players[$player->getName()] = $player;
    }

    public function removePlayer(Player $player): void {
        unset($this->players[$player->getName()]);
    }

    public function getMinPlayers(): int{
        return $this->minPlayers;
    }

    public function setMinPlayers(int $minPlayers): void{
        $this->minPlayers = $minPlayers;
    }

    public function isElo(): bool{
        return $this->elo;
    }

    public function setElo(bool $elo): void{
        $this->elo = $elo;
    }

    public function update(): void {
        $players = $this->getPlayers();

        if(count($players) >= $this->getMinPlayers()) {
            $__players = [];
            for($i = 1; $i <= $this->getMinPlayers(); $i++) {
                $__players[] = array_shift($this->players);
            }

            $pk = new MatchPacket();
            $pk->addData("group", "Training");
            $pk->addData("minigame", $this->getMinigame());
            $pk->addData("players", json_encode($__players));
            $pk->addData("elo", strval($this->isElo()));
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
        }
    }
}