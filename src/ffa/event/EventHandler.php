<?php

/*
 *  _							    _ _	   
 * | |   _   _ _ __   __ _ _ __ ___| | |_   _ 
 * | |  | | | | '_ \ / _` | '__/ _ \ | | | | |
 * | |__| |_| | | | | (_| | | |  __/ | | |_| |
 * |_____\__,_|_| |_|\__,_|_|  \___|_|_|\__, |
 *									    |___/ 
 * 
 * Author: Lunarelly
 * 
 * GitHub: https://github.com/Lunarelly
 * 
 * Telegram: https://t.me/lunarellyy
 * 
 */

namespace ffa\event;

use pocketmine\event\{
    Listener,
    entity\EntityDamageEvent,
    entity\EntityDamageByEntityEvent,
    player\PlayerInteractEvent,
    player\PlayerDeathEvent,
    player\PlayerDropItemEvent,
    block\BlockBreakEvent,
    block\BlockPlaceEvent
};
use pocketmine\{
    Player,
    Server
};
use ffa\Main;

use function str_replace;

class EventHandler implements Listener {

    public function __construct(Main $main) {
        $this->main = $main;
    }

    public function onDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if(!($entity instanceof Player)) {
            return;
        }
        # Disabling fall damage
        if($event->getCause() == EntityDamageEvent::CAUSE_FALL) {
            if($entity->getLevel() == $this->main->soupArena or $entity->getLevel() == $this->main->nodebuffArena or $entity->getLevel() == $this->main->gappleArena) {
                $event->setCancelled();
            }
        }
        if($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if(!($damager instanceof Player)) {
                return;
            }
            # Knockback settings
            switch($entity->getLevel()) {
                case $this->main->soupArena:
                    $event->setKnockback($this->main->getConfig()->getNested("soup.knockback"));
                    break;
                case $this->main->nodebuffArena:
                    $event->setKnockback($this->main->getConfig()->getNested("nodebuff.knockback"));
                    break;
                case $this->main->gappleArena:
                    $event->setKnockback($this->main->getConfig()->getNested("gapple.knockback"));
                    break;
            }
            # Only settings for FFA modes
            if($entity->getLevel() == $this->main->soupArena or $entity->getLevel() == $this->main->nodebuffArena or $entity->getLevel() == $this->main->gappleArena) {
                # Fake death
                if($entity->getHealth() < 2.1) {
                    $event->setCancelled();
                    switch($entity->getLevel()) {
                        case $this->main->soupArena:
                            foreach($entity->getLevel()->getPlayers() as $players) {
                                $players->sendMessage(str_replace(["{DEAD}", "{KILLER}", "{KILLER_HEALTH}", "{DEAD_SOUPS}", "{KILLER_SOUPS}"], [$entity->getDisplayName(), $damager->getDisplayName(), $damager->getHealth(), $this->main->countSoups($entity), $this->main->countSoups($damager)], $this->main->getConfig()->getNested("messages.broadcast.soup")));
                            }
                            $this->main->setSpectatorSettings($entity);
                            $entity->sendMessage(str_replace("{KILLER}", $damager->getDisplayName(), $this->main->getConfig()->getNested("messages.death")));
                            $this->main->addKill($damager);
                            $this->main->arenaRefill($damager, "soup");
                            $damager->sendMessage(str_replace("{DEAD}", $entity->getDisplayName(), $this->main->getConfig()->getNested("messages.kill")));
                            return true;
                            break;
                        case $this->main->nodebuffArena:
                            foreach($entity->getLevel()->getPlayers() as $players) {
                                $players->sendMessage(str_replace(["{DEAD}", "{KILLER}", "{KILLER_HEALTH}", "{DEAD_POTS}", "{KILLER_POTS}"], [$entity->getDisplayName(), $damager->getDisplayName(), $damager->getHealth(), $this->main->countPots($entity), $this->main->countPots($damager)], $this->main->getConfig()->getNested("messages.broadcast.nodebuff")));
                            }
                            $this->main->setSpectatorSettings($entity);
                            $entity->sendMessage(str_replace("{KILLER}", $damager->getDisplayName(), $this->main->getConfig()->getNested("messages.death")));
                            $this->main->addKill($damager);
                            $this->main->arenaRefill($damager, "nodebuff");
                            $damager->sendMessage(str_replace("{DEAD}", $entity->getDisplayName(), $this->main->getConfig()->getNested("messages.kill")));
                            return true;
                            break;
                        case $this->main->gappleArena:
                            foreach($entity->getLevel()->getPlayers() as $players) {
                                $players->sendMessage(str_replace(["{DEAD}", "{KILLER}", "{KILLER_HEALTH}"], [$entity->getDisplayName(), $damager->getDisplayName(), $damager->getHealth()], $this->main->getConfig()->getNested("messages.broadcast.gapple")));
                            }
                            $this->main->setSpectatorSettings($entity);
                            $entity->sendMessage(str_replace("{KILLER}", $damager->getDisplayName(), $this->main->getConfig()->getNested("messages.death")));
                            $this->main->addKill($damager);
                            $this->main->arenaRefill($damager, "gapple");
                            $damager->sendMessage(str_replace("{DEAD}", $entity->getDisplayName(), $this->main->getConfig()->getNested("messages.kill")));
                            return true;
                            break;
                    }        
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if($item->getId() == 388 && $item->getDamage() == 0 && $item->getCustomName() == $this->main->getConfig()->getNested("items.respawn")) {
            switch($player->getLevel()) {
                case $this->main->soupArena:
                    $this->main->joinArena($player, "soup");
                    break;
                case $this->main->nodebuffArena:
                    $this->main->joinArena($player, "nodebuff");
                    break;
                case $this->main->gappleArena:
                    $this->main->joinArena($player, "gapple");
                    break;
            }
            $player->sendMessage($this->main->getConfig()->getNested("messages.respawn"));
        }
        if($item->getId() == 351 && $item->getDamage() == 1 && $item->getCustomName() == $this->main->getConfig()->getNested("items.quit")) {
            if($player->getLevel() == $this->main->soupArena or $player->getLevel() == $this->main->nodebuffArena or $player->getLevel() == $this->main->gappleArena) {
                $this->main->quitArena($player);
                $player->sendMessage($this->main->getConfig()->getNested("messages.quit"));
            }
        }
    }

    public function onDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        if($player->getLevel() == $this->main->soupArena or $player->getLevel() == $this->main->nodebuffArena or $player->getLevel() == $this->main->gappleArena) {
            $event->setDrops([]);
        }
    }

    public function onDrop(PlayerDropItemEvent $event) {
        $player = $event->getPlayer();
        if($player->getLevel() == $this->main->soupArena or $player->getLevel() == $this->main->nodebuffArena or $player->getLevel() == $this->main->gappleArena) {
            $event->setCancelled();
        }
    }

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        if($player->getLevel() == $this->main->soupArena or $player->getLevel() == $this->main->nodebuffArena or $player->getLevel() == $this->main->gappleArena) {
            $event->setCancelled();
        }
    }

    public function onPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        if($player->getLevel() == $this->main->soupArena or $player->getLevel() == $this->main->nodebuffArena or $player->getLevel() == $this->main->gappleArena) {
            $event->setCancelled();
        }
    }
}