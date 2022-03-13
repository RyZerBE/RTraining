<?php

namespace ryzerbe\training\gameserver\minigame\type\clutches;

use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customitem\CustomItemManager;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\item\MinigameHubItem;
use ryzerbe\training\gameserver\minigame\trait\BlockSkinTrait;
use ryzerbe\training\gameserver\minigame\trait\BlockStorageTrait;
use ryzerbe\training\gameserver\minigame\trait\StatesTrait;
use ryzerbe\training\gameserver\minigame\type\clutches\entity\ClutchesEntity;
use ryzerbe\training\gameserver\minigame\type\clutches\item\ClutchesConfigurationItem;
use ryzerbe\training\gameserver\minigame\type\clutches\item\ClutchesStartItem;
use ryzerbe\training\gameserver\minigame\type\clutches\item\ClutchesStopItem;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\util\Countdown;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use ryzerbe\training\gameserver\util\ScoreboardUtils;
use function array_filter;
use function array_key_first;
use function array_search;
use function is_string;

class ClutchesGameSession extends GameSession {
    use BlockStorageTrait;
    use StatesTrait;
    use BlockSkinTrait;

    public const STATE_COUNTDOWN = 0;
    public const STATE_HITTING = 1;

    private int $platformId;

    private int $blockSaveLength = 0;
    private int $topBlockSaveLength = 0;

    private int $hitType = ClutchesMinigame::ONE_HIT;
    private float $knockBackLevel = ClutchesMinigame::EASY;
    private float $seconds = 5.0;
    private ?Item $blockSkin = null;

    private Countdown $countdown;

    public function __construct(Session $session, Level $level, int $platformId){
        $this->platformId = $platformId;
        $this->countdown = new Countdown($this->seconds * 20);
        parent::__construct($session, $level);
    }

    public function getPlatformId(): int{
        return $this->platformId;
    }

    public function getSpawn(): Location {
        return new Location(8.5 + ($this->getPlatformId() * 32), 51, 8.5, 0, 0, $this->getLevel());
    }

    public function reset(bool $teleport = true): void{
        $this->resetBlocks();
        /** @var PMMPPlayer $player */
        $player = $this->getSession()->getPlayer();
        if($player === null) return;
        $inventory = $player->getInventory();
        $inventory->clearAll();

        $configurationItem = CustomItemManager::getInstance()->getCustomItemByClass(ClutchesConfigurationItem::class);
        $configurationItem?->giveToPlayer($player, MinigameDefaultSlots::SLOT_CONFIGURATION_ITEM);

        if($this->isRunning()) {
            if($teleport) $player->teleport($this->getSpawn()->subtract(0, 0, 1));
            $inventory->setItem(MinigameDefaultSlots::SLOT_BLOCK_ITEM, $this->getBlockSkin());
            $item = CustomItemManager::getInstance()->getCustomItemByClass(ClutchesStopItem::class);
        } else {
            if($teleport) $player->teleport($this->getSpawn());
            $item = CustomItemManager::getInstance()->getCustomItemByClass(ClutchesStartItem::class);
        }

        if($item !== null) {
            $item->giveToPlayer($player, MinigameDefaultSlots::SLOT_OTHER_ITEM);
            $player->resetItemCooldown($item->getItem(), 10);
        }

        /** @var MinigameHubItem|null $leaveItem */
        $leaveItem = CustomItemManager::getInstance()->getCustomItemByClass(MinigameHubItem::class);
        $leaveItem?->giveToPlayer($player, MinigameDefaultSlots::SLOT_LEAVE_ITEM);

        $this->blockSaveLength = 0;

        $this->setState(self::STATE_COUNTDOWN);
        $this->countdown->setOriginCountdown($this->seconds * 20);
        $this->countdown->resetCountdown();
        $this->sendScoreboard();
    }

    public function getBlockSaveLength(): int{
        return $this->blockSaveLength;
    }

    public function setBlockSaveLength(int $blockSaveLength): void{
        $this->blockSaveLength = $blockSaveLength;
        if($blockSaveLength > $this->topBlockSaveLength){
            $this->topBlockSaveLength = $blockSaveLength;
        }
    }

    public function getEntity(): ?ClutchesEntity {
        $platformId = $this->getPlatformId();
        $entities = array_filter($this->getLevel()->getEntities(), function(Entity $entity) use ($platformId): bool {
            return $entity instanceof ClutchesEntity && $entity->getPlatformId() === $platformId;
        });
        return $entities[array_key_first($entities)] ?? null;
    }

    public function getTopBlockSaveLength(): int{
        return $this->topBlockSaveLength;
    }

    public function getKnockBackLevel(): float{
        return $this->knockBackLevel;
    }

    public function getHitType(): int{
        return $this->hitType;
    }

    public function getSeconds(): float{
        return $this->seconds;
    }

    public function getCountdown(): Countdown{
        return $this->countdown;
    }

    public function setHitType(int $hitType): void{
        $this->hitType = $hitType;
    }

    public function setKnockBackLevel(float $knockBackLevel): void{
        $this->knockBackLevel = $knockBackLevel;
    }

    public function setSeconds(float $seconds): void{
        $this->seconds = $seconds;
    }

    public function sendScoreboard(): void{
        $player = $this->getSession()->getPlayer();
        if($player === null) return;

        $key = array_search($this->getHitType(), ClutchesMinigame::HIT_TYPES);
        $hitType = !is_string($key) ? "???" : $key;
        $key = array_search($this->getKnockBackLevel(), ClutchesMinigame::KNOCKBACK_LEVELS);
        $knockBackLevel = !is_string($key) ? "???" : $key;

        ScoreboardUtils::rmScoreboard($player, "training");
        ScoreboardUtils::createScoreboard($player, $this->getSettings()->PREFIX, "training");
        ScoreboardUtils::setScoreboardEntry($player, 0, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 1, TextFormat::GRAY."○ Best length", "training");
        ScoreboardUtils::setScoreboardEntry($player, 2, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getTopBlockSaveLength(), "training");
        ScoreboardUtils::setScoreboardEntry($player, 3, TextFormat::GRAY."○ Length", "training");
        ScoreboardUtils::setScoreboardEntry($player, 4, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getBlockSaveLength(), "training");
        ScoreboardUtils::setScoreboardEntry($player, 5, TextFormat::GRAY."○ Knockback", "training");
        ScoreboardUtils::setScoreboardEntry($player, 6, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$knockBackLevel, "training");
        ScoreboardUtils::setScoreboardEntry($player, 7, TextFormat::GRAY."○ Hit", "training");
        ScoreboardUtils::setScoreboardEntry($player, 8, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$hitType, "training");
        ScoreboardUtils::setScoreboardEntry($player, 9, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 10, TextFormat::WHITE."⇨ ".TextFormat::AQUA."ryzer.be", "training");
    }

    public function getBlockSkins(): array{
        return [
            Item::get(BlockIds::SANDSTONE),
            Item::get(BlockIds::SLIME_BLOCK),
            Item::get(BlockIds::ICE),
            Item::get(BlockIds::PACKED_ICE),
        ];
    }
}