<?php

namespace ryzerbe\training\gameserver\minigame\type\aimtrainer\form;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\AimTrainerGameSession;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\entity\AimTrainerEntity;
use ryzerbe\training\gameserver\session\SessionManager;
use function array_keys;
use function array_search;
use function array_values;

class AimTrainerConfigurationForm {

    public const ITEM_LIST = [
        "Snowball" => ItemIds::SNOWBALL,
        "Bow" => ItemIds::BOW,
    ];

    public static function open(Player $player): void{
        $session = SessionManager::getInstance()->getSessionOfPlayer($player);
        if($session === null) return;

        $gameSession = $session->getGameSession();
        if(!$gameSession instanceof AimTrainerGameSession) return;

        $form = new CustomForm(function(Player $player, $data) use ($gameSession, $session): void{
            if($data === null) return;

            $itemId = array_values(self::ITEM_LIST)[$data["item"]] ?? BlockIds::ANVIL;

            $distance = $data["distance"];
            $spawn = $gameSession->getSpawn();

            $spawn->getLevel()->setBlock($gameSession->getBlockPosition(), Block::get(BlockIds::AIR));

            $gameSession->setDistance($distance);
            $gameSession->setItemId($itemId);

            $entity = $gameSession->getEntity();
            if($entity !== null && !$entity->isClosed()) {
                $entity->flagForDespawn();
            }

            $spawn->getLevel()->setBlock($gameSession->getBlockPosition(), Block::get(BlockIds::SEA_LANTERN));

            $aimTrainerEntity = new AimTrainerEntity($spawn->getLevel(), Entity::createBaseNBT($gameSession->getEntityPosition()));
            $aimTrainerEntity->namedtag->setString("Session", $session->getUniqueId());
            $aimTrainerEntity->setNameTag(TextFormat::GRAY.TextFormat::BOLD."Aim Trainer\n".TextFormat::AQUA."Ry".TextFormat::WHITE."Z".TextFormat::AQUA."er".TextFormat::WHITE."BE");
            $aimTrainerEntity->setNameTagAlwaysVisible();
            $aimTrainerEntity->spawnToAll();

            $gameSession->setEntity($aimTrainerEntity);

            $gameSession->resetHitCount(true);
            $gameSession->sendScoreboard();
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        });

        $form->addDropdown(TextFormat::RED."Item", array_keys(self::ITEM_LIST), array_search($gameSession->getItemId(), array_values(self::ITEM_LIST)), "item");
        $form->addSlider(TextFormat::RED."Distance", 4, 16, 2, $gameSession->getDistance(), "distance");
        $form->sendToPlayer($player);
    }
}