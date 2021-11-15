<?php

namespace ryzerbe\training\gameserver\minigame\type\aimtrainer;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customItem\CustomItemManager;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\item\MinigameHubItem;
use ryzerbe\training\gameserver\minigame\MinigameSettings;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\entity\AimTrainerEntity;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\item\AimTrainerConfigurationItem;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\item\AimTrainerResetItem;
use ryzerbe\training\gameserver\session\Session;
use ryzerbe\training\gameserver\util\MinigameDefaultSlots;
use ryzerbe\training\gameserver\util\ScoreboardUtils;

class AimTrainerGameSession extends GameSession {

    private int $platformId;
    private MinigameSettings $aimTrainerSettings;

    private int $hit = 0;
    private int $topHit = 0;

    private int $distance = 4;
    private int $itemId = ItemIds::SNOWBALL;

    private ?AimTrainerEntity $entity = null;

    public function __construct(Session $session, Level $level, int $platformId){
        $this->platformId = $platformId;
        $this->aimTrainerSettings = new AimTrainerSettings();
        parent::__construct($session, $level);
    }

    public function getPlatformId(): int{
        return $this->platformId;
    }

    public function getHitCount(): int{
        return $this->hit;
    }

    public function getTopHitCount(): int{
        return $this->topHit;
    }

    public function addHitCount(int $count = 1): void{
        $this->hit += $count;
        if($this->hit > $this->topHit) $this->topHit = $this->hit;
    }

    public function getDistance(): int{
        return $this->distance;
    }

    public function setDistance(int $distance): void{
        $this->distance = $distance;
    }

    public function getEntity(): ?AimTrainerEntity{
        return $this->entity;
    }

    public function setEntity(?AimTrainerEntity $entity): void{
        $this->entity = $entity;
    }

    public function getItemId(): int{
        return $this->itemId;
    }

    public function setItemId(int $itemId): void{
        $this->itemId = $itemId;
    }

    public function resetHitCount(bool $items = false): void{
        $this->hit = 0;

        if($items) {
            $itemId = $this->getItemId();
            /** @var PMMPPlayer $player */
            $player = $this->getSession()->getPlayer();
            if($player === null) return;

            $inventory = $player->getInventory();
            $inventory->clearAll();

            if($itemId === ItemIds::BOW) {
                $bow = Item::get($itemId);
                $bow->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::INFINITY)));

                $inventory->setItem(MinigameDefaultSlots::SLOT_PROJECTILE_ITEM, $bow);
                $inventory->setItem(16, Item::get(ItemIds::ARROW));
            }else {
                $inventory->setItem(MinigameDefaultSlots::SLOT_PROJECTILE_ITEM, Item::get($itemId, 0, 16));
            }

            /** @var AimTrainerConfigurationItem|null $settingsItem */
            $settingsItem = CustomItemManager::getInstance()->getCustomItemByClass(AimTrainerConfigurationItem::class);
            /** @var AimTrainerResetItem|null $resetItem */
            $resetItem = CustomItemManager::getInstance()->getCustomItemByClass(AimTrainerResetItem::class);
            /** @var MinigameHubItem|null $leaveItem */
            $leaveItem = CustomItemManager::getInstance()->getCustomItemByClass(MinigameHubItem::class);

            $resetItem?->giveToPlayer($player, MinigameDefaultSlots::SLOT_OTHER_ITEM);
            $leaveItem?->giveToPlayer($player, MinigameDefaultSlots::SLOT_LEAVE_ITEM);
            $settingsItem?->giveToPlayer($player, MinigameDefaultSlots::SLOT_CONFIGURATION_ITEM);
        }
    }

    public function getSettings(): AimTrainerSettings{
        return $this->aimTrainerSettings;
    }

    public function getSpawn(): Location {
        return new Location(8.5 + ($this->getPlatformId() * 32), 51, 8.5, 0, 0, $this->getLevel());
    }

    public function getBlockPosition(): Position {
        $spawn = $this->getSpawn()->floor();
        return new Position($spawn->x, $spawn->y - 1, $spawn->z + $this->getDistance(), $this->getLevel());
    }

    public function getEntityPosition(): Position{
        return Position::fromObject($this->getBlockPosition()->add(0.5, 1, 0.5), $this->getLevel());
    }

    public function sendScoreboard(): void{
        $player = $this->getSession()->getPlayer();
        if($player === null) return;
        ScoreboardUtils::rmScoreboard($player, "training");
        ScoreboardUtils::createScoreboard($player, $this->getSettings()->PREFIX, "training");
        ScoreboardUtils::setScoreboardEntry($player, 0, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 1, TextFormat::GRAY."○ Best hits", "training");
        ScoreboardUtils::setScoreboardEntry($player, 2, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getTopHitCount(), "training");
        ScoreboardUtils::setScoreboardEntry($player, 3, TextFormat::GRAY."○ Hits", "training");
        ScoreboardUtils::setScoreboardEntry($player, 4, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getHitCount(), "training");
        ScoreboardUtils::setScoreboardEntry($player, 5, TextFormat::GRAY."○ Distance", "training");
        ScoreboardUtils::setScoreboardEntry($player, 6, TextFormat::DARK_GRAY."⇨ ".TextFormat::GREEN.$this->getDistance(), "training");
        ScoreboardUtils::setScoreboardEntry($player, 7, "", "training");
        ScoreboardUtils::setScoreboardEntry($player, 8, TextFormat::WHITE."⇨ ".TextFormat::AQUA."ryzer.be", "training");
    }
}