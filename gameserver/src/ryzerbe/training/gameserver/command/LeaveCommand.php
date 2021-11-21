<?php

namespace ryzerbe\training\gameserver\command;

use BauboLP\Cloud\Bungee\BungeeAPI;
use BauboLP\Cloud\CloudBridge;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class LeaveCommand extends Command {
    public function __construct(){
        parent::__construct("leave", "Leave the minigame", "", ["l"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;
        $server = CloudBridge::getCloudProvider()->getRunningServersByGroup("TrainingLobby")[0] ?? null;
        if($server === null) return;
        BungeeAPI::transferPlayer($sender->getName(), $server);
    }
}