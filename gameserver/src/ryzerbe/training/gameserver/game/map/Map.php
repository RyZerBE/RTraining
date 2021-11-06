<?php

namespace ryzerbe\training\gameserver\game\map;

use baubolp\core\provider\AsyncExecutor;
use Closure;
use pocketmine\level\Level;
use pocketmine\Server;
use ryzerbe\training\gameserver\session\Session;
use function popen;
use function uniqid;

class Map {
    private GameMap $map;
    private Session $session;

    private ?Level $level = null;
    private string $uniqueLevelName;

    public function __construct(GameMap $map, Session $session){
        $this->map = $map;
        $this->session = $session;
    }

    public function getSession(): Session{
        return $this->session;
    }

    public function getMap(): GameMap{
        return $this->map;
    }

    public function getLevel(): ?Level{
        return ($this->level === null ? $this->level = Server::getInstance()->getLevelByName($this->uniqueLevelName) : $this->level);
    }

    public function setLevel(?Level $level): void{
        $this->level = $level;
    }

    public function load(Closure $closure): void{
        $levelName = $this->map->getMapName();
        $dataPath = Server::getInstance()->getDataPath();
        $this->uniqueLevelName = $levelId = uniqid();
        AsyncExecutor::submitMySQLAsyncTask("Lobby", function() use ($levelName, $levelId, $dataPath): void {
            popen("cp -R /root/RyzerCloud/data/MapDownloader/$levelName ".$dataPath."worlds/".$levelId, "r");
        }, function() use ($levelId, $closure): void {
            $server = Server::getInstance();
            $server->loadLevel($levelId);
            $level = $server->getLevelByName($levelId);
            $level->setTime(6000);
            $level->stopTime();
            ($closure)();
        });
    }
}