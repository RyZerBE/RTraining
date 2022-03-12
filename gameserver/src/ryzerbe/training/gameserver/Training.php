<?php

namespace ryzerbe\training\gameserver;

use BauboLP\Cloud\CloudBridge;
use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\core\util\customitem\CustomItemManager;
use ryzerbe\core\util\loader\ListenerDirectoryLoader;
use ryzerbe\training\gameserver\block\BedBlock;
use ryzerbe\training\gameserver\command\EnchantCommand;
use ryzerbe\training\gameserver\command\KitCommand;
use ryzerbe\training\gameserver\command\LeaveCommand;
use ryzerbe\training\gameserver\command\TFCommand;
use ryzerbe\training\gameserver\minigame\item\MinigameHubItem;
use ryzerbe\training\gameserver\minigame\MinigameManager;
use ryzerbe\training\gameserver\minigame\type\aimtrainer\AimTrainerMinigame;
use ryzerbe\training\gameserver\minigame\type\bridger\BridgerMinigame;
use ryzerbe\training\gameserver\minigame\type\clutches\ClutchesMinigame;
use ryzerbe\training\gameserver\minigame\type\hitblockclutch\HitBlockClutchMinigame;
use ryzerbe\training\gameserver\minigame\type\kitpvp\KitPvPMinigame;
use ryzerbe\training\gameserver\minigame\type\mlgrush\MLGRushMinigame;
use ryzerbe\training\gameserver\minigame\type\speedclutch\SpeedClutchMinigame;
use ryzerbe\training\gameserver\module\ModuleManager;
use ryzerbe\training\gameserver\scheduler\TrainingUpdateTask;

class Training extends PluginBase {
    public const PREFIX = TextFormat::BLUE.TextFormat::BOLD."Training ".TextFormat::RESET;

    private static Training $instance;

    /**
     * @throws ReflectionException
     */
    public function onEnable(): void{
        self::$instance = $this;
        ModuleManager::getInstance();

        ListenerDirectoryLoader::load($this, $this->getFile(), __DIR__ . "/listener/");
        $this->initEntity();
        $this->initMinigames();
        $this->initCustomItems();

        $this->getServer()->getCommandMap()->registerAll("Training", [
            new LeaveCommand(),
            new KitCommand(),
            new EnchantCommand(),
            new TFCommand()
        ]);

        $this->getScheduler()->scheduleRepeatingTask(new TrainingUpdateTask(), 1);

        BlockFactory::registerBlock(new BedBlock(), true);
    }

    public function onDisable(){
        foreach($this->getServer()->getOnlinePlayers() as $player) {
            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "leave");
        }
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
            new MLGRushMinigame(),
            new HitBlockClutchMinigame(),
            new SpeedClutchMinigame(),
        ];

        foreach($minigames as $minigame) {
            MinigameManager::registerMinigame($minigame);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function initCustomItems(): void{
        $items = [
            new MinigameHubItem(Item::get(ItemIds::IRON_DOOR)->setCustomName(TextFormat::RED."Leave"), 5),
        ];
        CustomItemManager::getInstance()->registerAll($items);
    }

    private function initEntity(): void{

    }
}