<?php

namespace ryzerbe\training\gameserver\game\map;

use Closure;
use pocketmine\level\Level;
use pocketmine\Server;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\training\gameserver\session\Session;
use function exec;
use function popen;
use function uniqid;

class Map {
    private ?GameMap $map;
    private ?Session $session;

    private ?Level $level = null;
    private string $uniqueLevelName;

    public function __construct(?GameMap $map, ?Session $session){
        $this->map = $map;
        $this->session = $session;
    }

    public function getSession(): ?Session{
        return $this->session;
    }

    public function getGameMap(): ?GameMap{
        return $this->map;
    }

    public function getLevel(): ?Level{
        return ($this->level === null ? $this->level = Server::getInstance()->getLevelByName($this->uniqueLevelName) : $this->level);
    }

    public function setLevel(?Level $level): void{
        $this->level = $level;
    }

    public function load(Closure $closure, ?string $mapName = null): void{
        $levelName = $mapName ?? $this->map?->getMapName();
        if($levelName === null) return;
        $dataPath = Server::getInstance()->getDataPath();
        $this->uniqueLevelName = $levelId = uniqid();
        AsyncExecutor::submitMySQLAsyncTask("Lobby", function() use ($levelName, $levelId, $dataPath): void {
            popen("cp -R /root/RyzerCloud/data/MapDownloader/$levelName ".$dataPath."worlds/".$levelId, "r");
        }, function() use ($levelId, $closure): void {
            $server = Server::getInstance();
            $server->loadLevel($levelId);
            $level = $server->getLevelByName($levelId);
            $level->setTime(1000);
            $level->stopTime();
            ($closure)();
        });
    }

    public function unload(): void {
        $level = $this->getLevel();
        $levelName = $level->getFolderName();
        if(Server::getInstance()->isLevelLoaded($levelName)){
            Server::getInstance()->unloadLevel($level);
        }
        $dataPath = Server::getInstance()->getDataPath();
        AsyncExecutor::submitMySQLAsyncTask("Lobby", function() use ($dataPath, $levelName): void {
            exec("rm -r " . $dataPath . "worlds/" . $levelName);
        });
    }
}