<?php

namespace ryzerbe\training\minigame\type\clutches\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\minigame\type\clutches\form\ClutchesSettingForm;
use function atan;
use function atan2;
use function rad2deg;
use function spl_object_id;

class ClutchesEntity extends Human implements ChunkLoader {
    public function __construct(Location $location, Skin $skin){
        $location->getLevelNonNull()->loadChunk($location->x >> 4, $location->z >> 4);
        $this->skin = $skin;
        parent::__construct($location->getLevelNonNull(), Entity::createBaseNBT($location, null, $location->yaw, $location->pitch));
    }

    public function initEntity(): void{
        parent::initEntity();

        $this->setNameTag(TextFormat::RED.TextFormat::BOLD."Clutcher\n".TextFormat::AQUA."Ry".TextFormat::WHITE."Z".TextFormat::AQUA."er".TextFormat::WHITE."BE");
        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();
        $this->setImmobile();
        $this->sendSkin();
    }

    public function onUpdate(int $currentTick): bool{
        foreach($this->getLevel()->getNearbyEntities($this->getBoundingBox()->expandedCopy(5, 5, 5)) as $viewer){
            if($viewer instanceof Player) {
                $dist = $this->distanceSquared($viewer);
                $dir = $viewer->subtract($this);
                $dir = $dist <= 0?$dir:$dir->divide($dist);

                $yaw = rad2deg(atan2(-$dir->getX(), $dir->getZ()));
                $pitch = rad2deg(atan(-$dir->getY()));

                $this->setRotation($this->yaw, $this->pitch);

                $pk = new MovePlayerPacket();
                $pk->entityRuntimeId = $this->getId();
                $pk->position = $this->getOffsetPosition($this->asVector3());
                $pk->yaw = $yaw;
                $pk->headYaw = $yaw;
                $pk->pitch = $pitch;
                $viewer->dataPacket($pk);
            }
        }
        return true;
    }

    public function attack(EntityDamageEvent $source): void{
        if(!$source instanceof EntityDamageByEntityEvent) return;
        $source->setCancelled();
        $damager = $source->getDamager();
        if(!$damager instanceof Player) return;

        ClutchesSettingForm::open($damager);
    }

    public function getLoaderId(): int{
        return spl_object_id($this);
    }

    public function isLoaderActive(): bool{
        return !$this->isClosed();
    }

    public function onChunkChanged(Chunk $chunk){}
    public function onChunkLoaded(Chunk $chunk){}
    public function onChunkUnloaded(Chunk $chunk){}
    public function onChunkPopulated(Chunk $chunk){}
    public function onBlockChanged(Vector3 $block){}
}