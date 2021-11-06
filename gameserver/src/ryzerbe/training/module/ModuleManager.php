<?php

namespace ryzerbe\training\module;

use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ryzerbe\training\module\setup\SetupModule;
use ryzerbe\training\Training;

class ModuleManager {
    use SingletonTrait;

    /** @var Module[]  */
    private array $modules = [];

    /**
     * ModuleManager constructor.
     *
     * Modules are used for systems that can be disabled in production
     * Do NOT implement game features here which are required in other parts of the plugin
     */
    public function __construct(){
        $modules = [
            SetupModule::getInstance(),
        ];
        foreach($modules as $module) {
            $this->registerModule($module);
        }
    }

    public function registerModule(Module $module): void {
        $this->modules[$module->getShortname()] = $module;
        $module->onLoad();

        Server::getInstance()->getPluginManager()->registerEvents($module, Training::getInstance());
    }
}