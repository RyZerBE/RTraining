<?php

namespace ryzerbe\training\module\setup\arena;

use baubolp\core\util\LocationUtils;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\utils\Config;
use ryzerbe\training\minigame\Minigame;
use ryzerbe\training\Training;
use function is_dir;
use function mkdir;

class SetupArena {
    private Level $level;
    private string $creator = "???";
    private Minigame $minigame;

    private Location $spectatorLocation;

    /** @var Location[]  */
    private array $teamLocations = [];

    public function __construct(Level $level, Minigame $minigame){
        $this->level = $level;
        $this->minigame = $minigame;
    }

    public function getLevel(): Level{
        return $this->level;
    }

    /**
     * @return Location[]
     */
    public function getTeamLocations(): array{
        return $this->teamLocations;
    }

    public function getSpectatorLocation(): Location{
        return $this->spectatorLocation;
    }

    public function setSpectatorLocation(Location $spectatorLocation): void{
        $this->spectatorLocation = $spectatorLocation;
    }

    public function setTeamLocation(Location $location, mixed $key = null): void {
        if($key === null){ $this->teamLocations[] = $location;
        } else { $this->teamLocations[$key] = $location; }
    }

    public function setCreator(string $creator): void{
        $this->creator = $creator;
    }

    public function getCreator(): string{
        return $this->creator;
    }

    public function getMinigame(): Minigame{
        return $this->minigame;
    }

    public function save(): void {
        if(!is_dir(Training::getInstance()->getDataFolder()."/maps")) mkdir(Training::getInstance()->getDataFolder()."/maps");
        if(!is_dir(Training::getInstance()->getDataFolder()."/maps/".$this->getMinigame()->getName())) mkdir(Training::getInstance()->getDataFolder()."/maps/".$this->getMinigame()->getName());
        $config = new Config(Training::getInstance()->getDataFolder()."/maps/".$this->getMinigame()->getName()."/".$this->getLevel()->getFolderName().".json");
        $config->set("SpectatorLocation", LocationUtils::toString($this->getSpectatorLocation()));
        $config->set("Creator", $this->getCreator());
        $config->set("Minigame", $this->getMinigame()->getName());

        foreach($this->getTeamLocations() as $key => $location) {
            $config->setNested("TeamLocations." . $key, LocationUtils::toString($location));
        }
        $config->save();
    }
}