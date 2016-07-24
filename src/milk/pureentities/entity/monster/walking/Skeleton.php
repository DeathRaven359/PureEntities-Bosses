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
use pocketmine\item\Stick;
use pocketmine\item\Item;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\network\protocol\AddEntityPacket;
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
    public function initEntity(){
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
         $this->setHealth(250);
         $this->getMaxHealth(250);
        }
        parent::initEntity();
        $this->created = true;
    }
    public function getName(){
        return "Skeleton";
    }
    
    //public function getSpeed() : float{
        //return 7.7;
    //}
	
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

            // @var Projectile $arrow 

                        $arrow = Entity::createEntity("FireBall", $this->chunk, $nbt, $this);

$fireball = Item::get(Item::FIRE_CHARGE, 0, 5);

             $ev = new EntityShootBowEvent($this, $fireball, $arrow, $f);//$this,$ev = new EntityShootBowEvent($this, Item::get(Item::FIRE_CHARGE, 0, 5), $arrow, $f);
            if(!($arrow instanceof FireBall)){
                return;
            }
            $this->server->getPluginManager()->callEvent($ev);
            
            //$arrow->setExplode(true);
            
            $arrow->setOnFire(100);
            $fireball->setOnFire(100);
            
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
		return true;
                }
            }
        }
    }




    public function spawnTo(Player $player){
        parent::spawnTo($player);

        $pk = new MobEquipmentPacket();
        $pk->eid = $this->getId();
        $pk->item = new Stick();
        $pk->slot = 10;
        $pk->selectedSlot = 10;
        $player->dataPacket($pk);
        //$this->level->addStrike($this->getViewers());
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
