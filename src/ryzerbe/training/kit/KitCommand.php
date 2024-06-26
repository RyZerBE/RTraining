<?php

namespace ryzerbe\training\kit;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\Training;

class KitCommand extends Command {

    public function __construct(){
        parent::__construct("kit", "Kit Admin Command", "", []);
        $this->setPermission("training.admin");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        if(empty($args[0])){
            $sender->sendMessage(Training::PREFIX.TextFormat::RED."/kit <create|edit|delete> <KitName>");
            return;
        }

        if(empty($args[1])){
            switch($args[0]){
                case "save":
                    $kitName = KitManager::getInstance()->editKit[$sender->getName()] ?? null;
                    if($kitName === null){
                        $sender->sendMessage(Training::PREFIX.TextFormat::RED."Ha Ha Ha Ha.. Du bist echt lustig...");
                        return;
                    }

                    $kit = new Kit($kitName, $sender->getInventory()->getContents(), $sender->getArmorInventory()->getContents());
                    KitManager::getInstance()->createKit($kit);
                    $sender->sendMessage(Training::PREFIX.TextFormat::GREEN."Das Kit ".TextFormat::AQUA.$kitName.TextFormat::GREEN." gespeichert.");
                    $inv = $sender->getInventory();
                    $aInv = $sender->getArmorInventory();
                    $inv->clearAll();
                    $aInv->clearAll();
                    $sender->setGamemode(0);
                    break;
            }
            return;
        }

        $kitName = $args[1];
        switch($args[0]){
            case "create":
                $kit = KitManager::getInstance()->getKitByName($kitName);
                if($kit !== null){
                    $sender->sendMessage(Training::PREFIX.TextFormat::RED."Das Kit existiert bereits!");
                    return;
                }

                $sender->getInventory()->clearAll();
                $sender->getArmorInventory()->clearAll();
                $sender->sendMessage(Training::PREFIX.TextFormat::GRAY."Nutze /kit save, um das Kit zu speichern.");
                KitManager::getInstance()->editKit[$sender->getName()] = $kitName;
                $sender->setGamemode(1);
                break;
            case "edit":
                $kit = KitManager::getInstance()->getKitByName($kitName);
                if($kit === null){
                    $sender->sendMessage(Training::PREFIX.TextFormat::RED."Das Kit existiert nicht!");
                    return;
                }

                $inv = $sender->getInventory();
                $aInv = $sender->getArmorInventory();
                $inv->clearAll();
                $aInv->clearAll();
                $inv->setContents($kit->getItems());
                $aInv->setContents($kit->getArmor());
                $sender->sendMessage(Training::PREFIX.TextFormat::GRAY."Nutze /kit save, um das Kit zu speichern.");
                KitManager::getInstance()->editKit[$sender->getName()] = $kitName;
                $sender->setGamemode(1);
                break;
            case "delete":
            case "del":
                $kit = KitManager::getInstance()->getKitByName($kitName);
                if($kit === null){
                    $sender->sendMessage(Training::PREFIX.TextFormat::RED."Das Kit existiert nicht!");
                    return;
                }

                KitManager::getInstance()->deleteKit($kit);
                $sender->sendMessage(Training::PREFIX.TextFormat::GREEN."Kit gelöscht!");
                break;
        }
    }
}