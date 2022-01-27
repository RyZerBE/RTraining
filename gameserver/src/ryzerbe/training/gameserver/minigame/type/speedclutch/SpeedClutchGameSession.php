<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\speedclutch;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItemManager;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\item\MinigameHubItem;
use ryzerbe\training\gameserver\minigame\trait\BlockStorageTrait;
use ryzerbe\training\gameserver\minigame\trait\StopWatchTrait;
use ryzerbe\training\gameserver\minigame\type\speedclutch\generator\SpeedClutchGenerator;
use ryzerbe\training\gameserver\minigame\type\speedclutch\item\SpeedClutchMinigameConfigurationItem;
use ryzerbe\training\gameserver\minigame\type\speedclutch\map\SpeedClutchMap;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use ryzerbe\training\gameserver\util\ScoreboardUtils;
use function number_format;
use function time;

class SpeedClutchGameSession extends GameSession {
    use BlockStorageTrait;
    use StopWatchTrait;

    private int $platformId;
    private int $seed;

    private SpeedClutchMap $map;

    private int $module = 1;

    public function __construct(Session $session, Level $level, int $platformId){
        $this->platformId = $platformId;
        $this->seed = time();
        parent::__construct($session, $level);
        $this->map = $this->generateMap();
    }

    public function getPlatformId(): int{
        return $this->platformId;
    }

    public function getSpawn(): Location {
        return new Location(8.5 + ($this->getPlatformId() * (SpeedClutchGenerator::ISLAND_INTERVAL * 16)), 51, 8.5, 0, 0, $this->getLevel());
    }

    public function getSeed(): int{
        return $this->seed;
    }

    public function setSeed(int $seed): void{
        $this->seed = $seed;
    }

    public function getMap(): SpeedClutchMap{
        return $this->map;
    }

    public function getModule(): int{
        return $this->module;
    }

    public function setModule(int $module): void{
        $this->module = $module;
    }

    public function generateMap(): SpeedClutchMap {
        $this->map = new SpeedClutchMap($this->getSeed());
        $this->map->generate($this);
        return $this->map;
    }

    public function placeBlock(Vector3 $vector3, Block $block): void {
        $level = $this->getLevel();
        if(!$level->isInWorld($vector3->x, $vector3->y, $vector3->z)) return;
        $level->loadChunk($vector3->getFloorX() >> 4, $vector3->getFloorZ() >> 4, true);
        $level->setBlockIdAt($vector3->x, $vector3->y, $vector3->z, $block->getId());
        $level->setBlockDataAt($vector3->x, $vector3->y, $vector3->z, $block->getDamage());
        $this->addBlock($level->getBlock($vector3));
    }

    public function resetGame(): void {
        $level = $this->getLevel();
        /** @var PMMPPlayer $player */
        $player = $this->getSession()->getPlayer();
        if($player === null) return;
        $this->resetBlocks("player");
        $this->module = 1;

        $this->stopTimer();

        $player->teleport($this->getSpawn());
        $player->getInventory()->clearAll();
        $this->sendScoreboard();

        $CIManager = CustomItemManager::getInstance();
        $CIManager->getCustomItemByClass(MinigameHubItem::class)?->giveToPlayer($player, MinigameDefaultSlots::SLOT_LEAVE_ITEM);
        $CIManager->getCustomItemByClass(SpeedClutchMinigameConfigurationItem::class)?->giveToPlayer($player, MinigameDefaultSlots::SLOT_CONFIGURATION_ITEM);
    }

    public function sendScoreboard(): void{
        $player = $this->getSession()->getPlayer();
        if($player === null) return;
        ScoreboardUtils::rmScoreboard($player, "training");
        ScoreboardUtils::createScoreboard($player, " ".$this->getSettings()->PREFIX, "training");
        ScoreboardUtils::setScoreboardEntry($player, 0, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 3, TextFormat::GRAY."○ Seed", "training");
        ScoreboardUtils::setScoreboardEntry($player, 4, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getSeed(), "training");
        ScoreboardUtils::setScoreboardEntry($player, 5, TextFormat::GRAY."○ Top time", "training");
        ScoreboardUtils::setScoreboardEntry($player, 6, TextFormat::DARK_GRAY."⇨ ".TextFormat::YELLOW.number_format($this->getTopScore(), 2)." Seconds", "training");
        ScoreboardUtils::setScoreboardEntry($player, 7, TextFormat::GRAY."○ Last time", "training");
        ScoreboardUtils::setScoreboardEntry($player, 8, TextFormat::DARK_GRAY."⇨ ".TextFormat::YELLOW.number_format($this->getScore(), 2)." Seconds", "training");
        ScoreboardUtils::setScoreboardEntry($player, 9, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 10, TextFormat::WHITE."⇨ ".TextFormat::AQUA."ryzer.be", "training");
    }
}