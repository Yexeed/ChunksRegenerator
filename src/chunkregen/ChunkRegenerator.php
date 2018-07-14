<?php declare(strict_types=1);
/**
 *  __     __                         _
 *  \ \   / /                        | |
 *   \ \_/ /___ __  __ ___   ___   __| |
 *    \   // _ \\ \/ // _ \ / _ \ / _` |
 *     | ||  __/ >  <|  __/|  __/| (_| |
 *     |_| \___|/_/\_\\___| \___| \__,_|
 *
 *           users are losers
 *               {2018}.
 */

namespace chunkregen;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\level\particle\Particle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class ChunkRegenerator extends PluginBase implements Listener
{

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("in game");
            return true;
        }
        switch ($command->getName()) {
            case "regen":
                $this->clearChunk($sender);
                $sender->getLevel()->regenerateChunk($sender->chunk->getX(), $sender->chunk->getZ());
                $sender->sendMessage("Chunk regenerated");
                break;
            case "show":
                $this->showSelection($sender);
                break;
        }
        return false;
    }

    public function clearChunk(Player $player){
        $chunk = $player->chunk;
        foreach($chunk->getSubChunks() as $subChunk){
            for($x = 0; $x < 16; $x++){
                for($y = 0; $y < 16; $y++){
                    for($z = 0; $z < 16; $z++){
                        $subChunk->setBlockId($x, $y, $z, Item::AIR);
                    }
                }
            }
        }
        $chunk->setChanged(true);
        $player->sendMessage("Cleared chunk " . $chunk->getX() . " " . $chunk->getZ());
    }

    public function onTap(PlayerInteractEvent $event){
        if($event->getItem()->getId() == Item::STICK && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            $b = $event->getBlock();
            $event->getPlayer()->sendTip("§e§l{$b->getName()}\n§b§l{$b->getId()}§f§l:§b§l{$b->getDamage()}");
        }
    }

    public function onMove(PlayerMoveEvent $event)
    {
        $event->getPlayer()->sendPopup("§l§aX: §c{$event->getPlayer()->getX()}" . " §aY: §c{$event->getPlayer()->getY()}" . " §aZ: §c{$event->getPlayer()->getZ()}" . "\n" . "§aChunk: §c" . $event->getPlayer()->chunk->getX() . " §f: §c" . $event->getPlayer()->chunk->getZ());
    }

    public function showSelection(Player $player): void
    {
        if ($player == null) return;
        if (!$player->isOnline()) return;
        $chunk = $player->chunk;
        $cX = $chunk->getX() << 4;
        $cZ = $chunk->getZ() << 4;
        $cXX = $cX + 16;
        $cZZ = $cZ + 16;
        $min = new Position($cX, 1, $cZ, $player->getLevel());
        $max = new Position($cXX, 256, $cZZ, $player->getLevel());
        $cubeLoc = $this->getCubePoints($min, $max);
        foreach ($cubeLoc as $pos) {
            $this->playEffect(new RedstoneParticle($this->centerLoc($pos), 5), $player);
        }
    }

    public function centerLoc(Position $pos){
        return new Position($pos->getFloorX() + 0.5, $pos->getFloorY() + 0.5, $pos->getFloorZ() + 0.5, $pos->getLevel());
    }

    public function playEffect(Particle $pt, Player $player) {
        $player->getLevel()->addParticle($pt, [$player]);
    }

    public function getCubePoints(Position $loc1, Position $loc2): array {
        $locs = [];
        $world = $loc1->getLevel();
        $xx = [$loc1->getFloorX(), $loc2->getFloorX()];
        $zz = [$loc1->getFloorZ(), $loc2->getFloorZ()];
        $yy = [];
        if (($loc2->getFloorY() - $loc1->getFloorY()) > 2) {
            $skip = false;
            for ($y = $loc2->getFloorY(); $y >= $loc1->getFloorY(); $y--) {
                if (!$skip) $yy[] = $y;

            }
        } else {
            $yy[] = $loc1->getFloorY();
            $yy[] = $loc2->getFloorY();
        }

        for ($x = $xx[0]; $x < $xx[1]; $x++)
            for ($y = 0; $y < count($yy); $y++) {
                $locs[] = new Location($x + 0.5, $yy[$y] + 0.5, $zz[0] + 0.5, 0, 0, $world);
                $locs[] = new Location($x + 0.5, $yy[$y] + 0.5, $zz[1] + 0.5, 0, 0, $world);
            }
        for ($z = $zz[0]; $z < $zz[1]; $z++)
            for ($y = 0; $y < count($yy); $y++) {
                $locs[] = new Location($xx[0] + 0.5, $yy[$y] + 0.5, $z + 0.5, 0, 0, $world);
                $locs[] = new Location($xx[1] + 0.5, $yy[$y] + 0.5, $z + 0.5, 0, 0, $world);
            }
        return $locs;
    }
}