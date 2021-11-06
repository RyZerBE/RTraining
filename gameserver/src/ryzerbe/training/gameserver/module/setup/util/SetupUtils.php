<?php

namespace ryzerbe\training\gameserver\module\setup\util;

use pocketmine\math\Vector3;
use function abs;
use function atan2;

class SetupUtils {
    public static function calculateYaw(Vector3 $vector3, Vector3 $targetPos, ?array $possibleRotations = null): int {
        $xDist = $vector3->x - $targetPos->x;
        $zDist = $vector3->z - $targetPos->z;
        $yawToBlock = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if($yawToBlock < 0) $yawToBlock += 360.0;
        $yaws = $possibleRotations ?? [45, 90, 135, 180, 225, 270, 315, 360];
        $yaw = null;
        foreach ($yaws as $tempYaw) if ($yaw === null || abs($yawToBlock - $yaw) > abs($tempYaw - $yawToBlock)) $yaw = $tempYaw;
        return $yaw;
    }
}