<?php

namespace ryzerbe\training\command;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMoveServerPacket;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class LeaveCommand extends Command {
    public function __construct(){
        parent::__construct("leave", "Leave the minigame", "", ["l"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;

        $pk = new PlayerMoveServerPacket();
        $pk->addData("serverName", "challenge");
        $pk->addData("playerNames", $sender->getName());
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
    }
}