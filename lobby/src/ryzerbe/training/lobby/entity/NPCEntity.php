<?php

namespace ryzerbe\training\lobby\entity;

use Closure;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\training\lobby\form\element\Button;
use ryzerbe\training\lobby\form\type\MinigameListForm;
use ryzerbe\training\lobby\minigame\Minigame;
use ryzerbe\training\lobby\minigame\MinigameManager;
use ryzerbe\training\lobby\queue\QueueManager;
use function array_filter;
use function array_map;
use function array_rand;
use function count;
use function mt_rand;
use function spl_object_id;

class NPCEntity extends Human implements ChunkLoader {
    private array $emotes = [];
    private int $emoteCooldown = 0;
    private string $lastEmote = "";
    private Location $location;

    private bool $lookAtPlayer = false;

    private ?Closure $interactClosure = null;
    private ?Closure $attackClosure = null;

    private ?string $group = null;
    private ?string $queue = null;

    protected $gravity = 0.0;

    public function __construct(Location|Level $location, Skin|CompoundTag $skin){
        if($location instanceof Level) {
            parent::__construct($location, $skin);
            $this->flagForDespawn();
            return;
        }
        $this->skin = $skin;
        $this->location = $location;
        $level = $location->getLevelNonNull();
        $level->registerChunkLoader($this, $location->x >> 4, $location->z >> 4, true);
        parent::__construct($level, Entity::createBaseNBT($location, null, $location->yaw, $location->pitch));
    }

    public function updateTitle(string $title, string $subtitle): void{
        $this->setNameTag($title."\n".$subtitle);
    }

    public function initEntity(): void{
        parent::initEntity();

        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();
        $this->sendSkin();
    }

    public function setEmotes(array $emotes): void {
        $this->emotes = $emotes;
    }

    public function addEmote(string $emote): self {
        $this->emotes[] = $emote;
        return $this;
    }

    public function getEmotes(): array {
        return $this->emotes;
    }

    public function getRandomEmote(): ?string {
        if($this->emotes === []) return null;
        return $this->emotes[array_rand($this->emotes)];
    }

    public function setInteractClosure(?Closure $closure): void {
        $this->interactClosure = $closure;
    }

    public function setAttackClosure(?Closure $closure): void {
        $this->attackClosure = $closure;
    }

    public function getGroup(): ?string{
        return $this->group;
    }

    public function setGroup(?string $group): void {
        $this->group = $group;
    }

    public function getQueue(): ?string{
        return $this->queue;
    }

    public function setQueue(?string $queue): void {
        $this->queue = $queue;
    }

    public function setLookAtPlayer(bool $lookAtPlayer): void {
        $this->lookAtPlayer = $lookAtPlayer;
    }

    public function setRotation(float $yaw, float $pitch): void{
        parent::setRotation($yaw, $pitch);
        $this->setForceMovementUpdate();
    }

    public function onUpdate(int $currentTick): bool{
        if($this->lookAtPlayer) {
            $target = $this->getLevel()->getNearestEntity($this, 40, Player::class);
            if($target !== null) {
                $this->lookAt($target->add(0, 1));
                $this->setForceMovementUpdate();
            }
        }

        if($this->getEmotes() !== [] && --$this->emoteCooldown <= 0) {
            $this->emoteCooldown = mt_rand(100, 300);
            $emote = $this->getRandomEmote();
            while($this->lastEmote === $emote) $emote = $this->getRandomEmote();
            $packet = EmotePacket::create($this->getId(), $emote, EmotePacket::FLAG_SERVER);
            $this->getLevel()->broadcastPacketToViewers($this, $packet);

            if(count($this->getEmotes()) > 1) $this->lastEmote = $emote;
        }
		$this->teleport($this->location);
        return parent::onUpdate($currentTick);
    }

    public function attack(EntityDamageEvent $source): void{
        if(!$source instanceof EntityDamageByEntityEvent) return;
        $player = $source->getDamager();
        if(!$player instanceof Player) return;
        if($this->attackClosure === null){
            $this->handleInteract($player);
            return;
        }
        ($this->attackClosure)($player, $this);
        $source->setCancelled();
    }

    public function onInteract(Player $player, Item $item, Vector3 $clickPos): bool{
        if($this->interactClosure === null){
            $this->handleInteract($player);
            return true;
        }
        ($this->interactClosure)($player, $this);
        return true;
    }

    protected function handleInteract(Player $player): void {
        switch(true) {
            case ($this->getQueue() !== null): {
                $queue = QueueManager::getInstance()->getQueue($this->getQueue());
                $queue?->handlePlayer($player);
                break;
            }
            case ($this->getGroup() !== null): {
                MinigameListForm::open($player, TextFormat::GOLD.$this->getGroup(), array_map(function(Minigame $minigame): Button {
                    return $minigame->isReleased() ? (
                    new Button(TextFormat::GOLD.$minigame->getName().TextFormat::EOL.TextFormat::DARK_GRAY."(".TextFormat::GREEN."Click to create session".TextFormat::DARK_GRAY.")", -1, "", $minigame->getName())
                    ) : (
                    new Button(TextFormat::GOLD.$minigame->getName().TextFormat::EOL.TextFormat::DARK_GRAY."(".TextFormat::RED."SOON".TextFormat::DARK_GRAY.")", -1, "", "soon")
                    );
                }, array_filter(MinigameManager::getInstance()->getMinigames(), function(Minigame $minigame): bool {
                    return (!$minigame->isBeta() || $minigame->isTeaser()) && !$minigame->isMultiplayer() && $minigame->getGroup() === $this->getGroup();
                })));
                break;
            }
        }
    }

    public function canSaveWithChunk(): bool{
        return false;
    }

    public function entityBaseTick(int $tickDiff = 1): bool{
        return true;
    }

    public function getLoaderId(): int{
        return spl_object_id($this);
    }

    public function isLoaderActive(): bool{
        return !$this->isClosed();
    }

    public function onChunkChanged(Chunk $chunk){}
    public function onChunkLoaded(Chunk $chunk){}
    public function onChunkUnloaded(Chunk $chunk){}
    public function onChunkPopulated(Chunk $chunk){}
    public function onBlockChanged(Vector3 $block){}
}