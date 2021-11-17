<?php

namespace ryzerbe\training\lobby\queue;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\MatchPacket;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\training\lobby\Training;
use function array_map;
use function array_shift;
use function count;
use function intval;
use function json_encode;
use function str_repeat;
use function strval;

class Queue {
    private string $minigame;

    /** @var Player[] */
    private array $players = [];

    private int $minPlayers = 2;
    private bool $elo = true;

    private int $points = 0;

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

    public function isPlayer(Player $player): bool {
        return isset($this->players[$player->getName()]);
    }

    public function handlePlayer(Player $player): void {
        if($this->isPlayer($player)) {
            $this->removePlayer($player);
            $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("message-queue-left", $player));
            $player->playSound("note.bass", 5.0, 1.0, [$player]);
            return;
        }
        $this->addPlayer($player);
        $player->playSound("note.bass", 5.0, 1.5, [$player]);
        $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("message-joined-queue", $player));
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

        if(Server::getInstance()->getTick() % 20 === 0){
            if(++$this->points > 3) $this->points = 0;
        }
        foreach($players as $player) {
            $player->sendTip(LanguageProvider::getMessageContainer("waiting-for-enemy", $player).str_repeat(".", $this->points));
        }

        if(count($players) >= $this->getMinPlayers()) {
            $__players = [];
            for($i = 1; $i <= $this->getMinPlayers(); $i++) {
                $__players[] = array_shift($this->players);
            }

            $pk = new MatchPacket();
            $pk->addData("group", "Training");
            $pk->addData("minigame", $this->getMinigame());
            $pk->addData("players", json_encode(array_map(function(Player $player): string {
                return $player->getName();
            }, $__players)));
            $pk->addData("teams", json_encode([
                "team_1" => [
                    "players" => [$__players[0]->getName()],
                    "data" => ["name" => "Team 1", "color" => "§b"]
                ],
                "team_2" => [
                    "players" => [$__players[1]->getName()],
                    "data" => ["name" => "Team 2", "color" => "§c"]
                ]
            ]));
            $pk->addData("elo", strval(intval($this->isElo())));
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
        }
    }
}