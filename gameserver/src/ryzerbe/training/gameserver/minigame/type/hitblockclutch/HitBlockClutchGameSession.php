<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\hitblockclutch;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
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
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\entity\HitBlockClutchEntity;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\generator\HitBlockClutchGenerator;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\item\HitBlockClutchMinigameConfigurationItem;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\map\HitBlockClutchMap;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use ryzerbe\training\gameserver\util\ScoreboardUtils;
use function array_filter;
use function number_format;
use function time;

class HitBlockClutchGameSession extends GameSession {
    use BlockStorageTrait;
    use StopWatchTrait;

    private int $platformId;
    private int $seed;

    private HitBlockClutchMap $map;

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
        return new Location(8.5 + ($this->getPlatformId() * (HitBlockClutchGenerator::ISLAND_INTERVAL * 16)), 51, 8.5, 0, 0, $this->getLevel());
    }

    public function getSeed(): int{
        return $this->seed;
    }

    public function setSeed(int $seed): void{
        $this->seed = $seed;
    }

    public function getMap(): HitBlockClutchMap{
        return $this->map;
    }

    public function getModule(): int{
        return $this->module;
    }

    public function setModule(int $module): void{
        $this->module = $module;
    }

    public function generateMap(): HitBlockClutchMap {
        $this->map = new HitBlockClutchMap($this->getSeed());
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

        $id = $this->getPlatformId();
        foreach(array_filter($level->getEntities(), function(Entity $entity) use ($id): bool {
            return $entity instanceof HitBlockClutchEntity && $entity->getGameSession()->getPlatformId() === $id;
        }) as $entity) $entity->flagForDespawn();

        $map = $this->getMap();
        for($module = 1; $module <= HitBlockClutchMap::TOTAL_MODULES; $module++) {
            foreach($map->getEnemyPositions($module) as $vector3) {
                $entity = new HitBlockClutchEntity(Location::fromObject($vector3, $this->getLevel(), 0, 0), $this->getSession()->getPlayer()->getSkin(), $this, $module);
                $entity->spawnToAll();
            }
        }

        $this->stopTimer();

        $player->teleport($this->getSpawn());
        $player->getInventory()->clearAll();
        $this->sendScoreboard();

        $CIManager = CustomItemManager::getInstance();
        $CIManager->getCustomItemByClass(MinigameHubItem::class)?->giveToPlayer($player, MinigameDefaultSlots::SLOT_LEAVE_ITEM);
        $CIManager->getCustomItemByClass(HitBlockClutchMinigameConfigurationItem::class)?->giveToPlayer($player, MinigameDefaultSlots::SLOT_CONFIGURATION_ITEM);
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