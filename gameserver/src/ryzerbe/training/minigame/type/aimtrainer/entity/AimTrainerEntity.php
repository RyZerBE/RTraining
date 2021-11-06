<?php

namespace ryzerbe\training\minigame\type\aimtrainer\entity;

use pocketmine\entity\passive\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use ryzerbe\training\minigame\type\aimtrainer\AimTrainerGameSession;
use ryzerbe\training\session\SessionManager;
use function spl_object_id;

class AimTrainerEntity extends Villager implements ChunkLoader {

    public function attack(EntityDamageEvent $source): void{
        $this->setHealth(20.0);
        $session = SessionManager::getInstance()->getSession($this->namedtag->getString("Session", ""));
        if($session === null) {
            $this->flagForDespawn();
            return;
        }
        /** @var AimTrainerGameSession $gameSession */
        $gameSession = $session->getGameSession();
        if($source->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $this->teleport($gameSession->getEntityPosition());
            $gameSession->resetHitCount(true);
            $gameSession->sendScoreboard();
            return;
        }
        if($source instanceof EntityDamageByEntityEvent) {
            if($source->getCause() !== EntityDamageEvent::CAUSE_PROJECTILE) return;
            $player = $session->getPlayer();

            $gameSession->addHitCount();
            $player->sendActionBarMessage(TextFormat::GREEN.$gameSession->getHitCount()." Hits");
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        }
        parent::attack($source);
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