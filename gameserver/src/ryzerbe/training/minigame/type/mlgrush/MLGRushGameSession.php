<?php

namespace ryzerbe\training\minigame\type\mlgrush;

use pocketmine\level\Level;
use pocketmine\utils\TextFormat;
use ryzerbe\training\game\GameSession;
use ryzerbe\training\game\map\GameMap;
use ryzerbe\training\game\team\Team;
use ryzerbe\training\minigame\trait\BlockStorageTrait;
use ryzerbe\training\minigame\type\kitpvp\maps\Map;
use ryzerbe\training\session\Session;
use ryzerbe\training\util\Countdown;
use ryzerbe\training\util\ScoreboardUtils;

class MLGRushGameSession extends GameSession {
    /** @var Map|null */
    private ?Map $map;
    /** @var Countdown|null  */
    private ?Countdown $countdown;
    /** @var array  */
    private array $placedBlocks = [];
    /** @var int  */
    public int $tick = 0;
    /** @var array  */
    public array $points = [];

    use BlockStorageTrait;

    /**
     * @param Session $session
     * @param Level|null $level
     * @param GameMap|null $map
     */
    public function __construct(Session $session, ?Level $level, ?GameMap $map){
        $this->map = $map;
        parent::__construct($session, $level);
    }

    /**
     * @return Map|null
     */

    public function getMap(): ?Map{
        return $this->map;
    }

    /**
     * @return null|Countdown
     */
    public function getCountdown(): ?Countdown{
        return $this->countdown;
    }

    public function stopCountdown(): void{
        $this->countdown = null;
    }

    /**
     * @param int $seconds
     * @param int $state
     */
    public function startCountdown(int $seconds, int $state){
        $this->countdown = new Countdown($seconds, $state);
    }

    /**
     * @param Team $team
     * @param int $points
     */
    public function addPoint(Team $team, int $points = 1){
        if(empty($this->points[$team->getName()])) $this->points[$team->getName()] = 0;

        $this->points[$team->getName()] += $points;
    }

    /**
     * @param Team $team
     * @return int
     */
    public function getPointsOfTeam(Team $team): int{
        if(empty($this->points[$team->getName()])) $this->points[$team->getName()] = 0;

        return $this->points[$team->getName()];
    }

    public function sendScoreboard(): void{
        foreach($this->getSession()->getOnlinePlayers() as $player) {
            ScoreboardUtils::rmScoreboard($player, "training");
            ScoreboardUtils::createScoreboard($player, $this->getSession()->getMinigame()->getSettings()->PREFIX, "training");
            ScoreboardUtils::setScoreboardEntry($player, 0, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, 1, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, 2, TextFormat::GRAY."○ Map", "training");
            ScoreboardUtils::setScoreboardEntry($player, 3, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getMap()->getName(), "training");
            ScoreboardUtils::setScoreboardEntry($player, 4, "", "training");
            $line = 4;
            foreach($this->getSession()->getTeams() as $team) {
                ScoreboardUtils::setScoreboardEntry($player, ++$line, TextFormat::GRAY."○ ".$team->getColor().$team->getName().TextFormat::DARK_GRAY." ⇨ ".TextFormat::WHITE.$this->getPointsOfTeam($team), "training");
            }
            ScoreboardUtils::setScoreboardEntry($player, ++$line, "", "training");
            ScoreboardUtils::setScoreboardEntry($player, ++$line, TextFormat::WHITE."⇨ ".TextFormat::AQUA."ryzer.be", "training");
        }
    }
}