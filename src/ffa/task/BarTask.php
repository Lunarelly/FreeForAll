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

namespace ffa\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use ffa\Main;

class BarTask extends PluginTask {

    public function __construct(Main $main) {
        $this->main = $main;
        parent::__construct($main);
    }

    public function onRun($currentTicks) {
        foreach(Server::getInstance()->getOnlinePlayers() as $players) {
            switch($players->getLevel()) {
                case $this->main->soupArena:
                    $this->main->sendArenaBar($players, "soup");
                    break;
                case $this->main->nodebuffArena:
                    $this->main->sendArenaBar($players, "nodebuff");
                    break;
                case $this->main->gappleArena:
                    $this->main->sendArenaBar($players, "gapple");
                    break;
            }
        }
    }
}