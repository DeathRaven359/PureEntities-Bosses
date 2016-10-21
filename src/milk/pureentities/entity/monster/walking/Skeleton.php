<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use milk\pureentities\entity\projectile\FireBall;
use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\entity\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\Timings;
use pocketmine\item\Bow;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\sound\DoorCrashSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddEntityPacket;

class Skeleton extends WalkingMonster implements Creature{
    
    const NETWORK_ID = 34;

    public $width = 0.65;
    public $height = 1.8;
    
    public function getName(){
        return "Skeleton";
    }

    public function getSpeed() : float{
        return 7.7;
    }
    public function initEntity(){
        parent::initEntity();
        $this->setMaxHealth(250);
        $this->setHealth(250);
    }
    public function attackEntity(Entity $player){
        if($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55){
            $this->attackDelay = 0;
            $attack = switch(mt_rand(0, 5));
            case 5:
            $player->setHealth($player->getHealth() - 1.2);
            $player->setOnFire(true);
            $plsyer->sendPopup(TextFormat::RED . "I'll kill you");
        }
    }
}
