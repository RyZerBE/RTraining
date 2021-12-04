<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\minigame\type\hitblockclutch\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\Player;
use ryzerbe\training\gameserver\game\GameSession;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\HitBlockClutchGameSession;
use function spl_object_id;

class HitBlockClutchEntity extends Human implements ChunkLoader {
    private GameSession $gameSession;
    private int $module;

    public function __construct(Location $location, Skin $skin, GameSession $gameSession, int $module){
        $this->skin = $skin;
        $this->gameSession = $gameSession;
        $this->module = $module;
        parent::__construct($location->getLevel(), Entity::createBaseNBT($location));
        $this->getLevel()->registerChunkLoader($this, $this->getFloorX() >> 4, $this->getFloorZ() >> 4, true);
    }

    /**
     * @return HitBlockClutchGameSession
     */
    public function getGameSession(): GameSession {
        return $this->gameSession;
    }

    public function getModule(): int {
        return $this->module;
    }

    public function onUpdate(int $currentTick): bool{
        return false;
    }

    public function attack(EntityDamageEvent $source): void{
        if(!$source instanceof EntityDamageByEntityEvent) {
            $this->flagForDespawn();
            return;
        }
        $player = $source->getDamager();
        if(!$player instanceof Player || ($gameSession = $this->getGameSession())->getSession()->getPlayer()?->getId() !== $player->getId() || $gameSession->getModule() !== $this->getModule()) return;
        $player->playSound("random.orb", 5.0, 1.0, [$player]);
        $this->flagForDespawn();
    }

    public function getLoaderId(): int{
        return spl_object_id($this);
    }

    public function isLoaderActive(): bool{
        return !$this->isClosed() && !$this->isFlaggedForDespawn();
    }

    public function onChunkChanged(Chunk $chunk){}
    public function onChunkLoaded(Chunk $chunk){}
    public function onChunkUnloaded(Chunk $chunk){}
    public function onChunkPopulated(Chunk $chunk){}
    public function onBlockChanged(Vector3 $block){}
}