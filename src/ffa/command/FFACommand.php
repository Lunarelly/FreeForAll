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

namespace ffa\command;

use pocketmine\command\{
    Command,
    CommandSender
};
use pocketmine\{
    Player,
    Server
};
use ffa\Main;

class FFACommand extends Command {

    public function __construct(Main $main) {
        $this->main = $main;
        parent::__construct("ffa");
    }

    public function execute(CommandSender $sender, $alias, array $args) {
        # Main command class
        if(!($sender instanceof Player)) {
            return $sender->sendMessage("Only in-game!");
        }
        if(!(isset($args[0]))) {
            return $sender->sendMessage("Usage: /ffa <join|quit|kills|info>");
        }
        $subcommands = ["join", "quit", "kills", "info"];
        if(!(in_array($args[0], $subcommands))) {
            return $sender->sendMessage("Unknown subcommand");
        }
        if($args[0] == "join") {
            if(!($sender->getLevel() == Server::getInstance()->getDefaultLevel())) {
                return $sender->sendMessage("You are not in lobby!");
            }
            if(!(isset($args[1]))) {
                return $sender->sendMessage("Usage: /ffa join <soup|nodebuff|gapple>");
            }
            if(!($args[1] == "soup" or $args[1] == "nodebuff" or $args[1] == "gapple")) {
                return $sender->sendMessage("Mode not found");
            }
            if($args[1] == "soup") {
                $this->main->joinArena($sender, "soup");
                return $sender->sendMessage("You've joined Soup FFA! Leave: /ffa quit");
            }
            if($args[1] == "nodebuff") {
                $this->main->joinArena($sender, "nodebuff");
                return $sender->sendMessage("You've joined Nodebuff FFA! Leave: /ffa quit");
            }
            if($args[1] == "gapple") {
                $this->main->joinArena($sender, "gapple");
                return $sender->sendMessage("You've joined Gapple FFA! Leave: /ffa quit");
            }
        }
        if($args[0] == "quit") {
            $arenas = [$this->main->soupArena, $this->main->nodebuffArena, $this->main->gappleArena];
            if(in_array($sender->getLevel(), $arenas)) {
                $this->main->quitArena($sender);
            } else {
                return $sender->sendMessage("You are not in FFA!");
            }
        }
        if($args[0] == "kills") {
            $kills = $this->main->getKills($sender);
            return $sender->sendMessage("Your FFA kills: " . $kills);
        }
        if($args[0] == "info") {
            return $sender->sendMessage("This server is running FFA v1.0.5\nAuthor: Lunarelly\nGitHub: https://github.com/Lunarelly");
        }
        return true;
    }
}