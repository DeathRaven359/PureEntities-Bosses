<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use milk\pureentities\entity\monster\FlyingMonster;
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

class Skeleton extends WalkingMonster implements ProjectileSource{
    const NETWORK_ID = 34;

    public $width = 0.65;
    public $height = 1.8;

    public function getName(){
        return "Skeleton";
    }
    
    public function setName(){
        return "Skeleton";
    }
    
    public function getSpeed() : float{
        return 7.7;
    }
    
    public function initEntity(){
        parent::initEntity();
        $this->setMaxHealth(10);
        $this->setMaxHealth(250);
        $this->setHealth(250);
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 30 && mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 55){
            $this->attackDelay = 0;
        
            $f = 1.2;
            $yaw = $this->yaw + mt_rand(-220, 220) / 10;
            $pitch = $this->pitch + mt_rand(-120, 120) / 10;
            $nbt = new CompoundTag("", [
                "Pos" => new ListTag("Pos", [
                    new DoubleTag("", $this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5)),
                    new DoubleTag("", $this->y + 1.62),
                    new DoubleTag("", $this->z +(cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5))
                ]),
                "Motion" => new ListTag("Motion", [
                    new DoubleTag("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f),
                    new DoubleTag("", -sin($pitch / 180 * M_PI) * $f),
                    new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $f)
                ]),
                "Rotation" => new ListTag("Rotation", [
                    new FloatTag("", $yaw),
                    new FloatTag("", $pitch)
                ]),
            ]);

            /** @var Projectile $arrow */
            $arrow = Entity::createEntity("FireBall", $this->chunk, $nbt, $this);

            $ev = new EntityShootBowEvent(Entity::createEntity("FireBall", $this->chunk, $nbt, $this), $arrow, $f);//$this, Item::get(Item::FIRE_CHARGE, 0, 5), $arrow, $f); // I need a way 
            $this->server->getPluginManager()->callEvent($ev);
            
            //$arrow->setExplode(true);
            
            $arrow->setOnFire(100);
            
            //$this->addStrike($arrow);

            $projectile = $ev->getProjectile();
            if($ev->isCancelled()){
                $projectile->kill();
            }elseif($projectile instanceof Projectile){
                $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($projectile));
                if($launch->isCancelled()){
                    $projectile->kill();
                }else{
                    $projectile->spawnToAll();
                    
                    $this->addStrike($projectile);
            
                    $projectile->setOnFire(100);
            
                    //$projectile->setExplode(true);
                    
                    $this->level->addSound(new DoorCrashSound($this), $this->getViewers());
                }
            }
        }
    }
    
    public function addStrike(Position $pos){
        $skully = $this->getEntity();
        $level = $this->getLevel();
        $light = new AddEntityPacket();
        $light->metadata = array();
        $light->type = 93;
        $light->eid = Entity::$entityCount++;
        $light->speedX = 0;
        $light->speedY = 0;
        $light->speedZ = 0;
        $light->x = $skully->x;
        $light->y = $skully->y;
        $light->z = $skully->z;
        Server::broadcastPacket($level->getPlayers(), $light);
    }

    public function spawnTo(Player $player){
        parent::spawnTo($player);

        $pk = new MobEquipmentPacket();
        $pk->eid = $this->getId();
        $pk->item = new Bow();
        $pk->slot = 10;
        $pk->selectedSlot = 10;
        $player->dataPacket($pk);
        //$this->level->addStrike($this->getViewers());
    }

    public function entityBaseTick($tickDiff = 1){
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $time = $this->getLevel()->getTime() % Level::TIME_FULL;
        if(
            !$this->isOnFire()
            && ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
        ){
            $this->setOnFire(0);
        }

        Timings::$timerEntityBaseTick->startTiming();
        return $hasUpdate;
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [
                Item::get(Item::DIAMOND, 0, mt_rand(0, 2)),
                Item::get(Item::DIAMOND_BLOCK, 0, mt_rand(0, 3)),
            ];
        }
        return [];
    }

}
