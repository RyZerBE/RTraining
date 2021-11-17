<?php

namespace ryzerbe\training\gameserver\session;

use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\game\team\Team;
use ryzerbe\training\gameserver\minigame\Minigame;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\util\Logger;
use function array_rand;
use function array_search;
use function count;
use function in_array;
use function uniqid;

class Session {
    private string $uniqueId;
    private array $players;
    private array $extraData;
    private string $minigame;

    private ?GameSession $gameSession = null;
    /** @var Team[]  */
    private array $teams = [];

    public function __construct(array $players, string $minigame, array $extraData){
        $this->uniqueId = uniqid();
        $this->players = $players;
        $this->extraData = $extraData;
        $this->minigame = $minigame;
    }

    public function getExtraData(): array{
        return $this->extraData;
    }

    public function getPlayers(): array{
        return $this->players;
    }

    public function getPlayer(): ?Player {
        $players = $this->getOnlinePlayers();
        if(empty($players)) return null;
        if(count($players) > 1) {
            Logger::error("Can not use getPlayer() for non solo games");
            return null;
        }
        return $players[array_rand($players)];
    }

    public function removePlayer(string $playerName){
        unset($this->players[array_search($playerName, $this->players)]);
    }

    public function addPlayer(string $playerName){
        $this->players[] = $playerName;
    }

    public function isPlayer(Player $player): bool {
        return in_array($player->getName(), $this->getPlayers());
    }

    public function getMinigame(): Minigame {
        return MinigameManager::getMinigame($this->minigame);
    }

    public function getUniqueId(): string{
        return $this->uniqueId;
    }

    public function getGameSession(): ?GameSession{
        return $this->gameSession;
    }

    public function setGameSession(?GameSession $gameSession): void{
        $this->gameSession = $gameSession;
    }

    /**
     * @return Player[]
     */
    public function getOnlinePlayers(): array {
        $players = [];
        foreach($this->getPlayers() as $player) {
            $player = Server::getInstance()->getPlayerExact($player);
            if($player !== null) $players[] = $player;
        }
        return $players;
    }

    public function canStart(): bool {
        return count($this->getPlayers()) <= count($this->getOnlinePlayers());
    }

    /**
     * @return Team[]
     */
    public function getTeams(): array{
        return $this->teams;
    }

    public function addTeam(Team $team): void{
        $this->teams[$team->getName()] = $team;
    }

    public function getTeam(string $team): ?Team {
        return $this->teams[$team] ?? null;
    }

    public function getTeamByPlayer(Player|string $player): ?Team {
        if($player instanceof Player) $player = $player->getName();
        foreach($this->getTeams() as $team) {
            if($team->isPlayer($player)) return $team;
        }
        return null;
    }
}