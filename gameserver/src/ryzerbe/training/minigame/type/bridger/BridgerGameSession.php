<?php

namespace ryzerbe\training\minigame\type\bridger;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use ryzerbe\training\game\GameSession;
use ryzerbe\training\minigame\trait\BlockStorageTrait;
use ryzerbe\training\minigame\trait\StopWatchTrait;
use ryzerbe\training\session\Session;
use ryzerbe\training\util\ScoreboardUtils;
use function array_keys;
use function array_search;
use function array_values;
use function cos;
use function deg2rad;
use function number_format;
use function sin;

class BridgerGameSession extends GameSession {
    use BlockStorageTrait;
    use StopWatchTrait;

    private int $platformId;
    private Location $spawn;

    private int $y = -1;
    private string $__rotation = "";
    private int $rotation = 0;
    private int $gradient = 0;
    private int $distance = 8;

    public function __construct(Session $session, Level $level, int $platformId){
        $this->platformId = $platformId;
        $this->spawn = new Location(8.5 + ($platformId * 160), (BridgerMinigame::BASE_Y + 1), 8.5, 0, 0, $level);
        parent::__construct($session, $level);
    }

    public function getPlatformId(): int{
        return $this->platformId;
    }

    public function getSpawn(): Location {
        return $this->spawn;
    }

    public function getRotation(): int{
        return $this->rotation;
    }

    public function __getRotation(): string {
        return $this->__rotation;
    }

    public function getDistance(): int{
        return $this->distance;
    }

    public function getGradient(): int{
        return $this->gradient;
    }

    public function getY(): int{
        return $this->y;
    }

    public function setRotation(int $rotation): void{
        $this->rotation = $rotation;
        $this->__rotation = array_keys(BridgerMinigame::ROTATION_LIST)[$this->getRotation()] ?? "default";
    }

    public function setDistance(int $distance): void{
        $this->distance = $distance;
    }

    public function setGradient(int $gradient): void{
        $this->gradient = $gradient;
    }

    public function setY(int $y): void{
        $oldY = $this->y;
        $this->y = $y;
        if($oldY !== $y) $this->generateGoalPlatform();
    }

    public function generateGoalPlatform(): void {
        if($this->y === -1) $this->y = BridgerMinigame::BASE_Y;
        $this->resetBlocks("platform");

        $rotation = $this->getRotation();
        $gradient = $this->getGradient();
        $distance = $this->getDistance();

        if($distance >= PHP_INT_MAX) return;

        $level = $this->getLevel();
        $vector3 = $this->getSpawn()->asVector3();
        if($gradient !== 0) {
            $vector3->y = BridgerMinigame::BASE_Y + $gradient;
        } else {
            $vector3->y = $this->getY();
        }

        $minY = BridgerMinigame::BASE_Y - 10;
        $maxY = Level::Y_MAX - 10;
        if($vector3->y > $maxY) $vector3->y = $maxY;
        if($vector3->y < $minY) $vector3->y = $minY;
        $this->y = $vector3->y;

        $blocks = [];
        for($__rotation = ($rotation - 4); $__rotation <= ($rotation + 4); $__rotation += 0.5) {
            $x = -1 * sin(deg2rad($__rotation));
            $z = cos(deg2rad($__rotation));
            $directionVector3 = (new Vector3($x, 0, $z))->normalize();
            $__vector3 = $vector3->add($directionVector3->multiply($distance));

            $__hash = Level::blockHash($__vector3->x, $__vector3->y, $__vector3->z);
            if(isset($blocks[$__hash])) continue;
            $___hash = Level::blockHash($__vector3->x, $__vector3->y + 1, $__vector3->z);

            $blocks[$__hash] = Block::get(BlockIds::GOLD_BLOCK, 0, Position::fromObject($__vector3, $level));
            $blocks[$___hash] = Block::get(BlockIds::LIGHT_WEIGHTED_PRESSURE_PLATE, 0, Position::fromObject($__vector3->add(0, 1), $level));
        }

        foreach($blocks as $block) {
            $level->setBlockIdAt($block->x, $block->y, $block->z, $block->getId());
            $level->setBlockDataAt($block->x, $block->y, $block->z, $block->getDamage());
            $this->addBlock($block, "platform");
        }
    }

    public function reset(): void {
        $this->sendScoreboard();
        $player = $this->getSession()->getPlayer();
        $player->teleport($this->getSpawn());
        $player->setImmobile(false);
        $this->resetBlocks();
        $this->resetTimer($this->__getRotation());
        $this->setY(BridgerMinigame::BASE_Y);
    }

    public function sendScoreboard(): void{
        $player = $this->getSession()->getPlayer();
        if($player === null) return;
        ScoreboardUtils::rmScoreboard($player, "training");
        ScoreboardUtils::createScoreboard($player, $this->getSettings()->PREFIX, "training");
        ScoreboardUtils::setScoreboardEntry($player, 0, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 1, TextFormat::GRAY."○ Distance", "training");
        ScoreboardUtils::setScoreboardEntry($player, 2, TextFormat::DARK_GRAY."⇨ ".TextFormat::YELLOW.$this->getDistance(), "training");
        ScoreboardUtils::setScoreboardEntry($player, 3, TextFormat::GRAY."○ Type", "training");
        ScoreboardUtils::setScoreboardEntry($player, 4, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.array_keys(BridgerMinigame::ROTATION_LIST)[array_search($this->getRotation(), array_values(BridgerMinigame::ROTATION_LIST))] ?? "???", "training");
        ScoreboardUtils::setScoreboardEntry($player, 5, TextFormat::GRAY."○ Top distance time", "training");
        ScoreboardUtils::setScoreboardEntry($player, 6, TextFormat::DARK_GRAY."⇨ ".TextFormat::YELLOW.number_format($this->getTopScore($this->__getRotation()), 2)." Seconds", "training");
        ScoreboardUtils::setScoreboardEntry($player, 7, TextFormat::GRAY."○ Last distance time", "training");
        ScoreboardUtils::setScoreboardEntry($player, 8, TextFormat::DARK_GRAY."⇨ ".TextFormat::YELLOW.number_format($this->getScore($this->__getRotation()), 2)." Seconds", "training");
        ScoreboardUtils::setScoreboardEntry($player, 9, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 10, TextFormat::WHITE."⇨ ".TextFormat::AQUA."ryzer.be", "training");
    }
}