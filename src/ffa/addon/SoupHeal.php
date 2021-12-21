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

namespace ffa\addon;

use pocketmine\event\{
    Listener,
    player\PlayerInteractEvent
};
use pocketmine\item\Item;
use pocketmine\level\sound\ClickSound;
use ffa\Main;

class SoupHeal implements Listener {

    public function __construct(Main $main) {
        $this->main = $main;
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        if($event->getItem()->getId() == 282) {
            if($player->getLevel() == $this->main->soupArena) {
                if($player->getHealth() >= 20) {
                    return;
                }
                $player->setHealth($player->getHealth() + 6);
                $player->getInventory()->removeItem(Item::get(282, 0, 1));
                $player->getLevel()->addSound(new ClickSound($player));
            }
        }
    }
}