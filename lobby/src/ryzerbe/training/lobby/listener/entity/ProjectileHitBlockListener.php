<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\listener\entity;

use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\Listener;

class ProjectileHitBlockListener implements Listener {
    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void {
        $event->getEntity()->flagForDespawn();
    }
}