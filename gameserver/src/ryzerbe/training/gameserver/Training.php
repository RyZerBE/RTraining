<?php

namespace ryzerbe\training;

use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use ReflectionException;
use ryzerbe\training\gameserver\command\EnchantCommand;
use ryzerbe\training\gameserver\command\KitCommand;
use ryzerbe\training\gameserver\command\LeaveCommand;
use ryzerbe\training\gameserver\game\map\GameMapManger;
use ryzerbe\training\gameserver\minigame\item\MinigameHubItem;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\AimTrainerMinigame;
use ryzerbe\training\gameserver\minigame\type\bridger\BridgerMinigame;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesMinigame;
use ryzerbe\training\gameserver\minigame\type\kitpvp\KitPvPMinigame;
use ryzerbe\training\gameserver\module\ModuleManager;
use ryzerbe\training\gameserver\scheduler\TrainingUpdateTask;
use ryzerbe\training\gameserver\util\customItem\CustomItemManager;

class Training extends PluginBase {
    public const PREFIX = TextFormat::BLUE.TextFormat::BOLD."Training ".TextFormat::RESET;

    private static Training $instance;

    /**
     * @throws ReflectionException
     */
    public function onEnable(): void{
        self::$instance = $this;
        CustomItemManager::getInstance();
        ModuleManager::getInstance();

        $this->initListener(__DIR__."/listener/");
        $this->initEntity();
        $this->initMinigames();
        $this->initCustomItems();

        $this->getServer()->getCommandMap()->registerAll("Training", [
            new LeaveCommand(),
            new KitCommand(),
            new EnchantCommand()
        ]);

        $this->getScheduler()->scheduleRepeatingTask(new TrainingUpdateTask(), 1);
    }

    public static function getInstance(): Training{
        return self::$instance;
    }

    private function initMinigames(): void{
        $minigames = [
            new ClutchesMinigame(),
            new AimTrainerMinigame(),
            new KitPvPMinigame(),
            new BridgerMinigame(),
        ];

        foreach($minigames as $minigame) {
            MinigameManager::registerMinigame($minigame);
        }
    }

    private function initCustomItems(): void{
        $items = [
            new MinigameHubItem(Item::get(ItemIds::IRON_DOOR)->setCustomName(TextFormat::RED."Leave"), 5),
        ];
        CustomItemManager::getInstance()->registerAll($items);
    }

    private function initEntity(): void{

    }

    /**
     * @throws ReflectionException
     */
    private function initListener(string $directory): void{
        foreach(scandir($directory) as $listener){
            if($listener === "." || $listener === "..") continue;
            if(is_dir($directory.$listener)){
                $this->initListener($directory.$listener."/");
                continue;
            }
            $dir = str_replace([$this->getFile()."src/", "/"], ["", "\\"], $directory);
            $refClass = new ReflectionClass($dir.str_replace(".php", "", $listener));
            $class = new ($refClass->getName());
            if($class instanceof Listener){
                Server::getInstance()->getPluginManager()->registerEvents($class, $this);
                Server::getInstance()->getLogger()->debug("Registered ".$refClass->getShortName()." listener");
            }
        }
    }
}