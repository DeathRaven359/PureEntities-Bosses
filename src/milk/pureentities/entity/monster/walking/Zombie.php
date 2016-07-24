<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\projectile\FireBall;
use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Ageable;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\entity\ProjectileSource;
use pocketmine\level\Location;
use pocketmine\level\sound\LaunchSound;


// BOSS ZOMBIES


class Zombie extends WalkingMonster implements Ageable{
    const NETWORK_ID = 32;

    public $width = 0.72;
    public $height = 1.8;

    public function getSpeed() : float{
        return 20.20;
    }

    public function initEntity(){
        parent::initEntity();
        
        //$this->fireProof = true;
        
        $this->setMaxHealth(100);
        
        $this->setHealth(100);
        
        if($this->getDataProperty(self::DATA_AGEABLE_FLAGS) == null){
            $this->setDataProperty(self::DATA_AGEABLE_FLAGS, self::DATA_TYPE_BYTE, 0);
        }
        $this->setDamage([0, 3, 5, 6]);
    }

    public function getName(){
        return "Zombie";
    }

    public function isBaby(){
        return $this->getDataFlag(self::DATA_AGEABLE_FLAGS, self::DATA_FLAG_BABY);
    }

    public function setHealth($amount){
        parent::setHealth($amount);

        if($this->isAlive()){
            if(15 < $this->getHealth()){
                $this->setDamage([0, 5, 10, 15]);
            }else if(10 < $this->getHealth()){
                $this->setDamage([0, 6, 12, 17]);
            }else if(5 < $this->getHealth()){
                $this->setDamage([0, 7, 13, 18]);
            }else{
                $this->setDamage([0, 8, 14, 19]);
            }
        }
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 2){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
            
            $fireball = Entity::createEntity("FireBall", $pos, $this);
            if(!($fireball instanceof FireBall)){
                return;
            }
            
            $fireball->setExplode(true);
            $fireball->setMotion(new Vector3(
                -sin(rad2deg($yaw)) * cos(rad2deg($pitch)) * $f * $f,
                -sin(rad2deg($pitch)) * $f * $f,
                cos(rad2deg($yaw)) * cos(rad2deg($pitch)) * $f * $f
            ));
            
            $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($fireball));
            if($launch->isCancelled()){
                $fireball->kill();
            }else{
                $fireball->spawnToAll();
                $this->level->addSound(new LaunchSound($this), $this->getViewers());
            }
        }
    }

    public function entityBaseTick($tickDiff = 1){
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $time = $this->getLevel()->getTime() % Level::TIME_FULL;
        if(
            !$this->isOnFire()
            && ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
        ){
            $this->setOnFire(100);
        }

        Timings::$timerEntityBaseTick->startTiming();
        return $hasUpdate;
    }

    public function getDrops(){
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 2)){
                case 0:
                    $drops[] = Item::get(Item::DIAMOND, 0, 5);
                    break;
                case 1:
                    $drops[] = Item::get(Item::DIAMOND_BLOCK, 0, 1); 
                    break;
                case 2:
                    $drops[] = Item::get(Item::STEAK, 0, 10);
                    break; //idk d:
            }
        }
        return $drops;
    }
}
