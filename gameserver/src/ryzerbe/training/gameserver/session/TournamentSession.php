<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\session;

use Exception;
use ryzerbe\core\util\animation\AnimationManager;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\game\map\Map;
use ryzerbe\training\gameserver\minigame\trait\StatesTrait;
use ryzerbe\training\gameserver\tournament\animation\DisplayEnemyAnimation;
use ryzerbe\training\gameserver\tournament\TournamentStateIds;

class TournamentSession extends Session {
    use StatesTrait;

    protected Map $map;

    public function getGameSession(): ?GameSession{
        return null;
    }

    public function setGameSession(?GameSession $gameSession): void{
        throw new Exception("This must not be called in a tournament session!");
    }

    public function getMap(): Map{
        return $this->map;
    }

    public function load(): void {
        $this->setState(TournamentStateIds::STATE_WAITING);

        $this->map = $map = new Map(null, null);
        $map->load(function() use ($map): void {
            $spawn = $map->getLevel()->getSafeSpawn()->add(0.5, 1, 0.5);
            foreach($this->getOnlinePlayers() as $player) {
                $player->teleport($spawn, 180, 0);
                $player->setImmobile(false);

                AnimationManager::getInstance()->addActiveAnimation(new DisplayEnemyAnimation($player, $this, $player->getName()));
            }
            $map->getLevel()->setTime(1000);
            $this->setRunning(true);

        }, "TournamentLobby");
    }

    public function onUpdate(int $tick): void {

    }
}