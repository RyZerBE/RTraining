<?php

namespace ryzerbe\training\kit;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\Training;

class EnchantCommand extends Command {

    public function __construct(){
        parent::__construct("e", "Enchant Command", "enchant", []);
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
            $sender->sendMessage(Training::PREFIX.TextFormat::RED."/enchant <Level>");
            return;
        }
        $level = $args[0];
        $form = new SimpleForm(function(Player $player, $data) use ($level): void{
            if($data === null) return;

            $item = $player->getInventory()->getItemInHand();
            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($data), $level));
            $player->getInventory()->setItemInHand($item);
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        });
        $form->setTitle(TextFormat::YELLOW.TextFormat::BOLD."Enchantments");

        $item = $sender->getInventory()->getItemInHand();
        for($id = 0; $id < 36; $id++){
            $enchantment = Enchantment::getEnchantment($id);
            if($enchantment === null) continue;
            if(!$enchantment->canApply($item)) continue;

            $form->addButton(TextFormat::DARK_PURPLE.$enchantment->getName(), -1, "", (string)$enchantment->getId());
        }

        $form->sendToPlayer($sender);
    }
}