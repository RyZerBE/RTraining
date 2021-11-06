<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches;

use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\item\MinigameHubItem;
use ryzerbe\training\gameserver\minigame\MinigameSettings;
use ryzerbe\training\gameserver\minigame\trait\BlockStorageTrait;
use ryzerbe\training\gameserver\minigame\type\clutches\item\ClutchesStartItem;
use ryzerbe\training\gameserver\minigame\type\clutches\item\ClutchesStopItem;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\util\customItem\CustomItemManager;
use ryzerbe\training\gameserver\util\customItem\TrainingItem;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use ryzerbe\training\gameserver\util\ScoreboardUtils;

class ClutchesGameSession extends GameSession {
    use BlockStorageTrait;

    private int $platformId;
    private float|int $lastHit, $lastBlock;
    private int $blockSaveLength = 0, $topBlockSaveLength = 0;

    /** @var ClutchesSettings  */
    private MinigameSettings $clutchesSettings;

    public function __construct(Session $session, Level $level, int $platformId){
        $this->platformId = $platformId;
        $this->clutchesSettings = new ClutchesSettings();
        $this->lastHit = 0;
        $this->lastBlock = 0;
        parent::__construct($session, $level);
    }

    public function getPlatformId(): int{
        return $this->platformId;
    }

    /**
     * @return ClutchesSettings
     */
    public function getSettings(): MinigameSettings{
        return $this->clutchesSettings;
    }

    public function getSpawn(): Location {
        return new Location(8.5+ ($this->getPlatformId() * 32), 51, 8.5, 0, 0, $this->getLevel());
    }

    public function reset(string $clutchesItemName = ClutchesStopItem::class): void{
        $this->resetBlocks();
        $player = $this->getSession()->getPlayer();
        if($player === null) return;
        $inventory = $player->getInventory();

        $player->teleport($this->getSpawn()->subtract(0, 0, 1));
        $inventory->clearAll();

        if($clutchesItemName !== ClutchesStartItem::class) {
            $inventory->setItem(MinigameDefaultSlots::SLOT_BLOCK_ITEM, Item::get(BlockIds::SANDSTONE, 0, 64));
        }
        /** @var TrainingItem|null $item */
        $item = CustomItemManager::getInstance()->getCustomItemByClass($clutchesItemName);
        $item?->giveItem($player, MinigameDefaultSlots::SLOT_OTHER_ITEM);

        /** @var MinigameHubItem|null $leaveItem */
        $leaveItem = CustomItemManager::getInstance()->getCustomItemByClass(MinigameHubItem::class);
        $leaveItem?->giveItem($player, MinigameDefaultSlots::SLOT_LEAVE_ITEM);

        $this->lastHit = 0;
        $this->lastBlock = 0;
        $this->blockSaveLength = 0;
        $this->sendScoreboard();
    }

    public function getLastBlock(): float|int{
        return $this->lastBlock;
    }

    public function getLastHit(): float|int{
        return $this->lastHit;
    }

    public function setLastBlockTime(float|int $lastBlock): void{
        $this->lastBlock = $lastBlock;
    }

    public function setLastHitTime(float|int $lastHit): void{
        $this->lastHit = $lastHit;
    }

    public function getBlockSaveLength(): int{
        return $this->blockSaveLength;
    }

    public function setBlockSaveLength(int $blockSaveLength): void{
        $this->blockSaveLength = $blockSaveLength;
        if($blockSaveLength > $this->topBlockSaveLength)
            $this->topBlockSaveLength = $blockSaveLength;
    }

    public function getTopBlockSaveLength(): int{
        return $this->topBlockSaveLength;
    }

    public function sendScoreboard(): void{
        $player = $this->getSession()->getPlayer();
        if($player === null) return;

        $knockBackString = match($this->getSettings()->knockBackLevel){
            ClutchesSettings::EASY => "Easy",
            ClutchesSettings::NORMAL => "Normal",
            ClutchesSettings::HARD => "Hard",
            default => "???"
        };

        $hitString = match ($this->getSettings()->hit) {
            ClutchesSettings::ONE_HIT => "One hit",
            ClutchesSettings::DOUBLE_HIT => "Double hit",
            ClutchesSettings::TRIPLE_HIT => "Triple hit",
            ClutchesSettings::QUADRUPLE_HIT => "Quadruple hit",
            default => "???"
        };
        ScoreboardUtils::rmScoreboard($player, "training");
        ScoreboardUtils::createScoreboard($player, $this->getSettings()->PREFIX, "training");
        ScoreboardUtils::setScoreboardEntry($player, 0, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 1, TextFormat::GRAY."○ Best length", "training");
        ScoreboardUtils::setScoreboardEntry($player, 2, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getTopBlockSaveLength(), "training");
        ScoreboardUtils::setScoreboardEntry($player, 3, TextFormat::GRAY."○ Length", "training");
        ScoreboardUtils::setScoreboardEntry($player, 4, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getBlockSaveLength(), "training");
        ScoreboardUtils::setScoreboardEntry($player, 5, TextFormat::GRAY."○ Knockback", "training");
        ScoreboardUtils::setScoreboardEntry($player, 6, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$knockBackString, "training");
        ScoreboardUtils::setScoreboardEntry($player, 7, TextFormat::GRAY."○ Hit", "training");
        ScoreboardUtils::setScoreboardEntry($player, 8, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$hitString, "training");
        ScoreboardUtils::setScoreboardEntry($player, 9, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 10, TextFormat::WHITE."⇨ ".TextFormat::AQUA."ryzer.be", "training");
    }
}