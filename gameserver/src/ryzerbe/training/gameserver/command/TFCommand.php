<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\EntityIds;
use pocketmine\Server;
use ReflectionClass;
use function strtolower;
use function ucfirst;

class TFCommand extends Command {
    public function __construct(){
        parent::__construct("tf", "");
        $this->setPermission("ryzer.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof ConsoleCommandSender) return;
        $entities = [];
        foreach(Server::getInstance()->getLevels() as $level) {
            foreach($level->getEntities() as $entity) {
                if(!isset($entities[$entity::NETWORK_ID])) {
                    $entities[$entity::NETWORK_ID] = 0;
                }
                $entities[$entity::NETWORK_ID]++;
            }
        }

        $entityList = [];
        foreach((new ReflectionClass(EntityIds::class))->getConstants() as $name => $value) {
            $entityList[$value] = ucfirst(strtolower($name));
        }

        foreach($entities as $entity => $count) {
            $sender->sendMessage(($entityList[$entity] ?? "Unknown")." -> ". $count);
        }
    }
}