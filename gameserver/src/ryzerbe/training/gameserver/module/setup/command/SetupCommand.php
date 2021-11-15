<?php

namespace ryzerbe\training\gameserver\module\setup\command;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\module\setup\arena\SetupArena;
use ryzerbe\training\gameserver\module\setup\SetupModule;
use ryzerbe\training\gameserver\Training;
use ryzerbe\training\gameserver\util\WaitingQueue;
use function is_dir;
use function is_file;
use function popen;
use function scandir;

class SetupCommand extends Command {

    public function __construct(){
        parent::__construct("setup", "administrator command","", [""]);
        $this->setPermission("training.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        WaitingQueue::removePlayer($sender);
        $sender->removeAllEffects();
        $sender->setImmobile(false);
        if(!isset($args[0])) {
            $form = new SimpleForm(function(Player $player, $data): void {
                if($data === null) return;
                $map = $data;
                $form = new CustomForm(function(Player $player, $data) use ($map): void {
                    if($data === null) return;
                    $creator = $data["creator"];
                    if(empty($creator)) return;

                    $form = new SimpleForm(function(Player $player, $data) use ($map, $creator): void{
                        if($data === null) return;

                        Server::getInstance()->dispatchCommand($player, "setup " . $map . " " . $creator." ".$data);
                    });

                    $form->setTitle("§lMap Setup");
                    foreach(MinigameManager::getMinigames() as $minigame) {
                        $form->addButton($minigame->getName(), 0, "", $minigame->getName());
                    }
                    $form->sendToPlayer($player);
                });
                $form->setTitle("§lMap Setup");
                $form->addInput("Creator", "", "", "creator");
                $form->sendToPlayer($player);
            });
            $form->setTitle("§lMap Setup");
            foreach(scandir("/root/RyzerCloud/data/MapDownloader/") as $map) {
                if(!is_file("/root/RyzerCloud/data/MapDownloader/" . $map . "/level.dat")) continue;
                $form->addButton($map, 0, "", $map);
            }
            $form->sendToPlayer($sender);
            return;
        }

        if(!isset($args[1]) || empty($args[0]) || empty($args[1]) || empty($args[2])){
            $sender->sendMessage("§8» §r§7/setup [Level] [Creator] [Minigame]");
            return;
        }
        $level = $args[0];
        $creator = $args[1];


        $minigame = MinigameManager::getMinigame($args[2]);
        if($minigame === null) return;

        if(!is_dir("worlds/".$level)){
            if(!is_dir("/root/RyzerCloud/data/MapDownloader/" . $level)) {
                $sender->sendMessage("§8» §r§7Unknown map " . $level . ".");
                return;
            }

            popen("cp -R /root/RyzerCloud/data/MapDownloader/$level ".Training::getInstance()->getServer()->getDataPath()."worlds/".$level, "r");
            $sender->sendMessage("§8» §r§7Successfully downloaded map " . $level . ".");
            Server::getInstance()->dispatchCommand($sender, "setup " . $level . " " . $creator." ".$minigame->getName());
            return;
        }
        Server::getInstance()->loadLevel($level);
        $level = Server::getInstance()->getLevelByName($level);

        if($sender->getLevel()->getName() !== $level->getName()){
            $sender->teleport($level->getSpawnLocation());
        }

        $arenaSetup = new SetupArena($level, $minigame);
        $arenaSetup->setCreator($creator);
        SetupModule::getInstance()->setArena($arenaSetup);
        foreach(SetupModule::getInstance()->getItems() as $item)
            $sender->getInventory()->addItem($item);

        $sender->sendMessage("§8» §r§7Setup Items received.");
        $sender->setGamemode(1);
        $sender->sendMessage("§8» §r§7Let`s go! Your setup is ready.");
    }
}