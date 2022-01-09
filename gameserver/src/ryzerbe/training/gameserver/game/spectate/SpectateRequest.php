<?php

namespace ryzerbe\training\gameserver\game\spectate;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMoveServerPacket;
use pocketmine\Server;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\training\gameserver\Training;
use ryzerbe\training\gameserver\util\WaitingQueue;
use function implode;
use function time;

class SpectateRequest {
    private array $playerNames;
    private string $target;

    private int $time;

    public function __construct(array $playerNames, string $target){
        $this->playerNames = $playerNames;
        $this->target = $target;
        $this->time = time() + 30;

        $pk = new PlayerMoveServerPacket();
        $pk->addData("playerNames", implode(":", $playerNames));
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
    }

    public function getPlayerNames(): array{
        return $this->playerNames;
    }

    public function getTarget(): string{
        return $this->target;
    }

    public function isValid(): bool{
        return time() < $this->time;
    }

    public function progress(): bool{
        if(Server::getInstance()->getPlayerExact($this->getTarget()) === null) {
            SpectateQueue::removeRequest($this);
            return false;
        }

        foreach($this->getPlayerNames() as $playerName){
            $player = RyZerPlayerProvider::getRyzerPlayer($playerName);
            if($player === null || !$player->getPlayer()->spawned) return false;
        }
        return true;
    }

    public function accept(): void{
        SpectateQueue::removeRequest($this);

        $target = Server::getInstance()->getPlayerExact($this->getTarget());
        if($target === null){
            foreach($this->getPlayerNames() as $playerName){
                $player = Server::getInstance()->getPlayerExact($playerName);
                if($player === null) continue;
                $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-match-not-found", $player->getName()));
                CloudBridge::getCloudProvider()->dispatchProxyCommand($playerName, "leave");
            }
            return;
        }

        foreach($this->getPlayerNames() as $playerName) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if($player === null) continue;
            SpectateManager::spectate($target, $player);
            WaitingQueue::removePlayer($player);
            $player->setImmobile(false);
            $player->setGamemode(3);
            $player->removeAllEffects();
            $player->teleport($target);
            $player->sendMessage(Training::PREFIX.LanguageProvider::getMessageContainer("training-match-found", $player->getName()));
        }
    }
}