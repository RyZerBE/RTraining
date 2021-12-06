<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\tournament;

use pocketmine\Player;
use ryzerbe\training\lobby\minigame\Minigame;

class Tournament {
    private Player $host;

    private Minigame $minigame;
    private int $playerCount;
    private bool $public;

    private int $id;

    /** @var Player[]  */
    private array $players = [];
    /** @var Player[]  */
    private array $invitedPlayers = [];

    public function __construct(Player $host, Minigame $minigame, int $playerCount, bool $public, int $id){
        $this->host = $host;
        $this->minigame = $minigame;
        $this->playerCount = $playerCount;
        $this->public = $public;
        $this->id = $id;

        $this->addPlayer($host);
    }

    public function getHost(): Player{
        return $this->host;
    }

    public function isHost(Player $player): bool {
        return $player->getId() === $this->host->getId();
    }

    public function getMinigame(): Minigame{
        return $this->minigame;
    }

    public function getPlayerCount(): int{
        return $this->playerCount;
    }

    public function isPublic(): bool{
        return $this->public;
    }

    public function getId(): int{
        return $this->id;
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

    /**
     * @return Player[]
     */
    public function getInvitedPlayers(): array{
        return $this->invitedPlayers;
    }

    public function addInvitedPlayer(Player $player): void {
        $this->invitedPlayers[$player->getName()] = $player;
    }

    public function removeInvitedPlayer(Player $player): void {
        unset($this->invitedPlayers[$player->getName()]);
    }

    public function isInvitedPlayer(Player $player): bool {
        return isset($this->invitedPlayers[$player->getName()]);
    }
}