<?php

namespace ryzerbe\training\gameserver\block;

use pocketmine\block\Bed;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class BedBlock extends Bed {
    public function onActivate(Item $item, Player $player = null): bool{
        return false;
    }

    public function onEntityFallenUpon(Entity $entity, float $fallDistance): void{
        $motion = $entity->getMotion();
        $entity->setMotion(new Vector3($motion->x, 0, $motion->z));
        $entity->noDamageTicks = 0;
    }
}