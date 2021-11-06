<?php

namespace ryzerbe\training\gameserver\game\match;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMoveServerPacket;
use baubolp\core\player\RyzerPlayerProvider;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\game\team\Team;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\session\SessionManager;
use ryzerbe\training\gameserver\util\Logger;
use function implode;
use function time;

class MatchRequest {

    private string $minigameName;
    private int $time;
    private array $playerNames;

    private array $extraData = [];

    private array $teams = [];
    private bool $elo;

    public function __construct(array $playerNames, string $miniGameName, bool $elo = false){
        $this->playerNames = $playerNames;
        $this->minigameName = $miniGameName;
        $this->time = time() + 30;
        $this->elo = $elo;

        $pk = new PlayerMoveServerPacket();
        $pk->addData("playerNames", implode(":", $playerNames));
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
    }

    public function getMinigameName(): string{
        return $this->minigameName;
    }

    public function getPlayerNames(): array{
        return $this->playerNames;
    }

    public function isValid(): bool{
        return time() < $this->time;
    }

    public function setTeams(array $teams): void {
        $this->teams = $teams;
    }

    public function getTeams(): array{
        return $this->teams;
    }

    public function progress(): bool{
        if(MinigameManager::getMinigame($this->minigameName) === null) {
            MatchQueue::removeQueue($this);
            Logger::error("Received MatchPacket with unknown minigame");
            return false;
        }

        foreach($this->getPlayerNames() as $playerName){
            $player = RyzerPlayerProvider::getRyzerPlayer($playerName);
            if($player === null || !$player->getPlayer()->spawned) return false;
        }
        return true;
    }

    public function accept(): void{
        $minigame = MinigameManager::getMinigame($this->minigameName);
        $playerNames = $this->playerNames;

        $session = new Session($playerNames, $minigame->getName(), $this->getExtraData());

        foreach($this->getTeams() as $__team) {
            $players = $__team["players"];
            $data = $__team["data"];

            $team = new Team($session, $data["name"], $data["color"]);
            foreach($players as $player) {
                $team->addPlayer(Server::getInstance()->getPlayerExact($player));
            }
            $session->addTeam($team);
        }

        SessionManager::getInstance()->addSession($session);
        $minigame->getSessionManager()->addSession($session);
        foreach($session->getOnlinePlayers() as $player) {
            $player->sendTitle(TextFormat::GREEN."Session found", TextFormat::GRAY."loading game..");
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        }

        $gameSession = $session->getGameSession();
        $gameSession->getSettings()->elo = $this->elo;
        MatchQueue::removeQueue($this);
    }

    public function getExtraData(): array{
        return $this->extraData;
    }

    public function addExtraData(string $key, $value){
        $this->extraData[$key] = $value;
    }

    public function removeExtraData(string $key){
        unset($this->extraData[$key]);
    }

    public function withElo(): bool{
        return $this->elo;
    }
}