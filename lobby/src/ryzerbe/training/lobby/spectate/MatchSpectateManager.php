<?php

namespace ryzerbe\training\lobby\spectate;

use pocketmine\utils\SingletonTrait;
use function array_search;

class MatchSpectateManager {
    use SingletonTrait;

    /** @var MatchSpectate[] */
    private array $matches = [];

    /**
     * @param MatchSpectate $matchSpectate
     */
    public function addMatchSpectate(MatchSpectate $matchSpectate){
        $this->matches[$matchSpectate->getMatchId()] = $matchSpectate;
    }

    /**
     * @param MatchSpectate $matchSpectate
     */
    public function removeMatchSpectate(MatchSpectate $matchSpectate){
        unset($this->matches[$matchSpectate->getMatchId()]);
    }

    /**
     * @param MatchSpectate[][] $matches
     */
    public function setMatches(array $matches): void{
        $this->matches = $matches;
    }

    /**
     * @return MatchSpectate[]
     */
    public function getMatches(): array{
        return $this->matches;
    }

    /**
     * @param string $id
     * @return MatchSpectate|null
     */
    public function getMatchById(string $id): ?MatchSpectate{
        return $this->matches[$id] ?? null;
    }
}