<?php

namespace ryzerbe\training\gameserver\module\setup\item;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\level\Location;
use pocketmine\level\particle\DustParticle;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\util\customItem\CustomItem;
use ryzerbe\training\gameserver\module\setup\SetupModule;
use ryzerbe\training\gameserver\module\setup\util\SetupUtils;
use function mt_rand;

class TeamSpawnPositionSetupItem extends CustomItem {
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $item = $event->getItem();
        $arena = SetupModule::getInstance()->getArena();
        $player = $event->getPlayer();
        if(!$this->checkItem($item) || $player->hasItemCooldown($item) || $arena === null) return;
        $player->resetItemCooldown($item, 5);
        $block = $event->getBlock();

        $position = $block->floor()->add(0.5, 0, 0.5);
        $location = new Location($position->x, $position->y, $position->z, SetupUtils::calculateYaw($player, $position), 0, $player->getLevel());

        $form = new SimpleForm(function(Player $player, $data) use ($arena, $position, $location): void {
            if($data === null) return;

            for ($n = 1; $n <= 20; $n++) {
                $player->getLevel()->addParticle(new DustParticle($position->add(0, $n / 10), mt_rand(), mt_rand(), mt_rand(), mt_rand()));
            }
            $arena->setTeamLocation($location, $data);
        });
        $form->setTitle("§lSetup");

        $form->addButton(TextFormat::RED."Red", 1, "https://media.discordapp.net/attachments/693494109842833469/871400780157034516/Rot3.png?width=468&height=468", "Red");
        $form->addButton(TextFormat::AQUA."Blue", 1, "https://media.discordapp.net/attachments/693494109842833469/871401481855713280/BLau.png?width=468&height=468", "Blue");
        $form->sendToPlayer($player);
    }
}