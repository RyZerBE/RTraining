<?php

declare(strict_types=1);

namespace ryzerbe\training\gameserver\tournament\animation;

use pocketmine\level\particle\GenericParticle;
use pocketmine\level\particle\Particle;
use pocketmine\network\mcpe\protocol\OnScreenTextureAnimationPacket;
use pocketmine\Player;
use ryzerbe\core\util\animation\PlayerAnimation;
use ryzerbe\training\gameserver\session\TournamentSession;
use function array_rand;
use function lcg_value;
use function mt_rand;

class DisplayEnemyAnimation extends PlayerAnimation {
    private string $enemy;
    private TournamentSession $session;

    public function __construct(Player $player, TournamentSession $session, string $enemy){
        $this->enemy = $enemy;
        $this->session = $session;
        parent::__construct($player);
    }

    public function getEnemy(): string{
        return $this->enemy;
    }

    public function getSession(): TournamentSession{
        return $this->session;
    }

    public function tick(): void{
        parent::tick();
        $player = $this->getPlayer();
        $session = $this->getSession();
        $tick = $this->getCurrentTick();

        if($tick <= 100) {
            if($tick % match (true) {
                    ($tick > 90) => 30,
                    ($tick > 75) => 15,
                    ($tick > 60) => 10,
                    default => 5
                } === 0) {
                //$players = $session->getOnlinePlayers(); //TODO
                //$player->sendTitle("§l§6".$tempPlayer->getName(), "", 0, 20, 0);
                $players = [
                    "Gustaf", "Peter", "Lukas", "Apfel", "Kuchen"
                ];
                $tempPlayer = $players[array_rand($players)];
                $player->sendTitle("§l§6".$tempPlayer, "", 0, 20, 0);
                $player->playSound("random.click", 5.0, 1.0, [$player]);
            }
            return;
        }
        $packet = new OnScreenTextureAnimationPacket();
        $packet->effectId = 11;
        $player->sendDataPacket($packet);

        $level = $player->getLevel();
        for($int = 0; $int <= 30; $int++){
            $level->addParticle(new GenericParticle($player->add((mt_rand(-1, 1) * lcg_value()), $player->getEyeHeight(), (mt_rand(-1, 1) * lcg_value())), Particle::TYPE_TOTEM));
        }

        $player->sendTitle("§l §r", "§l§6»       «\n\n§l§6".$this->getEnemy(), 5, 10, 30);
        $player->playSound("random.totem", 5.0, 1.0, [$player]);
        $this->stop();
    }
}