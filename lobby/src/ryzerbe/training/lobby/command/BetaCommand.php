<?php

declare(strict_types=1);

namespace ryzerbe\training\lobby\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use ryzerbe\training\lobby\form\type\BetaMinigamesForm;

class BetaCommand extends Command {
    public function __construct(){
        parent::__construct("beta", "Test beta games");
        $this->setPermission("training.cmd.beta");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player || !$this->testPermission($sender)) return;
        BetaMinigamesForm::open($sender);
    }
}