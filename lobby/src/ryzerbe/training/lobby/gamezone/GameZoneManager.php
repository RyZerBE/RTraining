<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\gamezone;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\ItemUtils;
use ryzerbe\training\lobby\item\TrainingItemManager;

class GameZoneManager {
    use SingletonTrait;
    public const MIN_Y = 144;
    public const MAX_Y = 180;

    /** @var Player[] */
    private array $players = [];

    /**
     * @return Player[]
     */
    public function getPlayers(): array{
        return $this->players;
    }

    public function addPlayer(Player $player): void {
        $this->players[$player->getName()] = $player;
        $this->resetGameZoneItems($player);
    }

    public function removePlayer(Player $player): void {
        unset($this->players[$player->getName()]);
        $this->resetLobbyItems($player);
    }

    public function isPlayer(Player $player): bool {
        return isset($this->players[$player->getName()]);
    }

    public function resetGameZoneItems(Player $player): void {
        $inventory = $player->getInventory();
        $armorInventory = $player->getArmorInventory();

        $armorInventory->clearAll();
        $inventory->setContents([
            0 => ItemUtils::addEnchantments(Item::get(ItemIds::DIAMOND_SWORD), [
                Enchantment::UNBREAKING => 5,
                Enchantment::SHARPNESS => 1,
            ])->setCustomName("§r§6Diamond Sword"),
            1 => ItemUtils::addEnchantments(Item::get(ItemIds::GOLDEN_PICKAXE)->setCustomName("§r§6Pickaxe"), [
                Enchantment::EFFICIENCY => 1,
                Enchantment::UNBREAKING => 5,
            ]),
            2 => Item::get(BlockIds::SANDSTONE, 0, 64)->setCustomName("§r§6Sandstone"),
            3 => Item::get(ItemIds::GOLDEN_APPLE, 0, 12)->setCustomName("§r§6Golden Apple"),
            6 => Item::get(ItemIds::ARROW, 0, 16)->setCustomName("§r§6Arrow"),
            7 => Item::get(BlockIds::COBWEB, 0, 2)->setCustomName("§r§6Cobweb"),
            8 => Item::get(ItemIds::BOW)->setCustomName("§r§6Bow"),
        ]);
        $armorInventory->setContents([
            0 => ItemUtils::addEnchantments(Item::get(ItemIds::DIAMOND_HELMET), [
                Enchantment::UNBREAKING => 3,
                Enchantment::PROTECTION => 1,
            ])->setCustomName("§r§6Diamond Helmet"),
            1 => ItemUtils::addEnchantments(Item::get(ItemIds::DIAMOND_CHESTPLATE), [
                Enchantment::UNBREAKING => 3,
                Enchantment::PROTECTION => 1,
            ])->setCustomName("§r§6Diamond Chestplate"),
            2 => ItemUtils::addEnchantments(Item::get(ItemIds::DIAMOND_LEGGINGS), [
                Enchantment::UNBREAKING => 3,
                Enchantment::PROTECTION => 1,
            ])->setCustomName("§r§6Diamond Leggings"),
            3 => ItemUtils::addEnchantments(Item::get(ItemIds::DIAMOND_BOOTS), [
                Enchantment::UNBREAKING => 3,
                Enchantment::PROTECTION => 1,
            ])->setCustomName("§r§6Diamond Boots"),
        ]);

        $inventory->setHeldItemIndex(0);
        $player->extinguish();
        $player->setHealth(20.0);
        $player->doCloseInventory();
    }

    /**
     * @param PMMPPlayer $player
     */
    public function resetLobbyItems(Player $player): void {
        $inventory = $player->getInventory();
        $armorInventory = $player->getArmorInventory();

        $player->extinguish();
        $player->setHealth(20.0);
        $player->doCloseInventory();
        $player->removeAllEffects();
        $armorInventory->clearAll();
        $inventory->clearAll();
        foreach(TrainingItemManager::getInstance()->getItems() as $trainingItem) {
            $trainingItem->giveToPlayer($player);
        }
        $inventory->setHeldItemIndex(4);
    }

    private array $scheduledBlocks = [];
    /** @var Block[]  */
    private array $blocks = [];

    public function scheduleBlock(Block $block, int $delay = 100): void {
        $block->getLevel()->broadcastLevelEvent($block, LevelEventPacket::EVENT_BLOCK_START_BREAK, (int) (65535 / $delay));
        $hash = Level::blockHash($block->getFloorX(), $block->getFloorY(), $block->getFloorZ());
        $this->blocks[$hash] = $block;
        $this->scheduledBlocks[(Server::getInstance()->getTick() + $delay)][] = $hash;
    }

    public function removeBlock(Block $block, bool $ignoreScheduledBlocks = false): void {
        $hash = Level::blockHash($block->getFloorX(), $block->getFloorY(), $block->getFloorZ());
        unset($this->blocks[$hash]);
        if(!$ignoreScheduledBlocks) {
            foreach($this->scheduledBlocks as $tick => $scheduledBlocks) {
                foreach($scheduledBlocks as $key => $scheduledBlockHash) {
                    if($scheduledBlocks !== $hash) continue;
                    unset($this->scheduledBlocks[$tick][$key]);
                    break;
                }
            }
        }
    }

    public function isBlock(Block $block): bool {
        return isset($this->blocks[Level::blockHash($block->getFloorX(), $block->getFloorY(), $block->getFloorZ())]);
    }

    public function onUpdate(): void {
        $tick = Server::getInstance()->getTick();
        $level = Server::getInstance()->getDefaultLevel();
        foreach(($this->scheduledBlocks[$tick] ?? []) as $hash) {
            $block = $this->blocks[$hash] ?? null;
            if($block === null) continue;
            Level::getBlockXYZ($hash, $x, $y, $z);
            $level->addParticle(new DestroyBlockParticle(new Vector3($x, $y, $z), $level->getBlockAt($x, $y, $z)));
            $level->setBlockIdAt($x, $y, $z, $block->getId());
            $level->setBlockDataAt($x, $y, $z, $block->getDamage());
            $this->removeBlock($block, true);
        }
        unset($this->scheduledBlocks[$tick]);
    }
}