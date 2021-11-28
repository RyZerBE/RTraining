<?php

namespace ryzerbe\training\gameserver\command;

use BauboLP\Cloud\Bungee\BungeeAPI;
use BauboLP\Cloud\CloudBridge;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\OnScreenTextureAnimationPacket;
use pocketmine\Player;
use pocketmine\Server;
use function array_rand;
use function intval;

class LeaveCommand extends Command {
    public function __construct(){
        parent::__construct("leave", "Leave the minigame", "", ["l"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            $player = Server::getInstance()->getOnlinePlayers()[array_rand(Server::getInstance()->getOnlinePlayers())];

            $packet = new OnScreenTextureAnimationPacket();
            $packet->effectId = intval($args[0] ?? 1);
            $player->sendDataPacket($packet);
            return;
        }
        $server = CloudBridge::getCloudProvider()->getRunningServersByGroup("TrainingLobby")[0] ?? null;
        if($server === null) return;
        BungeeAPI::transferPlayer($sender->getName(), $server);
    }
}