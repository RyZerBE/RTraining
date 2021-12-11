<?php

namespace ryzerbe\training\gameserver\game\team;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\gameserver\minigame\MinigameSettings;
use ryzerbe\training\gameserver\session\Session;
use function array_filter;
use function count;

class Team {
    private Session $session;

    private int $id;
    private string $name;
    private string $color;

    /** @var Player[]  */
    private array $players = [];

    private int $elo = 1000;

    public function __construct(Session $session, int $id, string $name, string $color) {
        $this->session = $session;
        $this->id = $id;
        $this->name = $name;
        $this->color = $color;
    }

    public function getSession(): Session{
        return $this->session;
    }

    public function getId(): int{
        return $this->id;
    }

    public function getColor(): string {
        return $this->color;
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array {
        return $this->players;
    }

    public function addPlayer(Player $player): void {
        $this->players[$player->getName()] = $player;
    }

    public function join(Player $player): void{
        $this->addPlayer($player);
    }

    public function leave(Player $player): void{
        $this->removePlayer($player);
    }

    public function removePlayer($player): void {
        if($player instanceof Player) $player = $player->getName();
        unset($this->players[$player]);
    }

    public function isPlayer($player): bool {
        if($player instanceof Player) $player = $player->getName();
        return isset($this->players[$player]);
    }

    public function isFull(MinigameSettings $minigameSettings): bool {
        return count($this->getPlayers()) >= $minigameSettings->maxPlayers;
    }

    public function isAlive(): bool {
        return count(array_filter($this->getPlayers(), function(Player $player): bool {
                return $player->isConnected();
            })) > 0;
    }

    public function getElo(): int{
        return $this->elo;
    }

    public function setElo(int $elo): void{
        $this->elo = $elo;
    }

    public function getBlockMeta(): int {
        return match ($this->getColor()) {
            TextFormat::RED => 14,
            TextFormat::BLUE, TextFormat::AQUA => 11,
            TextFormat::YELLOW => 4,
            TextFormat::GREEN, TextFormat::DARK_GREEN => 5,
            TextFormat::LIGHT_PURPLE => 6,
            TextFormat::GOLD => 1,
            TextFormat::DARK_PURPLE => 10,
            default => 0,
        };
    }

    public function getSimilarColors(): array {
        return match ($this->getBlockMeta()) {//THIS IS BULLSHIT
            14 => ["Rot"],
            11 => ["Magenta", "Light Blue", "Pink", "Purple", "Blue"],
            default => ["White"],
        };
    }

    public static function getTeamNameByBlocKMeta(int $blockMeta): string {
        return match ($blockMeta) {
            14 => "Rot",
            11 => "Blau",
            4 => "Gelb",
            5 => "Grün",
            6 => "Pink",
            1 => "Orange",
            10 => "Lila",
            default => "Weiß",
        };
    }
}