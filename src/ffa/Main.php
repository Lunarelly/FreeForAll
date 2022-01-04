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

namespace ffa;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\entity\Effect;
use pocketmine\{
    Player,
    Server
};
use pocketmine\level\sound\{
    AnvilFallSound,
    EndermanTeleportSound,
    ExplodeSound
};
use ffa\{
    addon\SoupHeal,
    command\FFACommand,
    event\EventHandler,
    task\BarTask
};

use function strtolower;
use function in_array;
use function str_replace;
use function str_repeat;

final class Main extends PluginBase {

    public $modes = ["soup", "nodebuff", "gapple"];

    public function onEnable() {
        Server::getInstance()->getCommandMap()->register("ffa", new FFACommand($this));
        Server::getInstance()->getPluginManager()->registerEvents(new EventHandler($this), $this);
        Server::getInstance()->getPluginManager()->registerEvents(new SoupHeal($this), $this);
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new BarTask($this), 20);
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "data");
        $this->saveDefaultConfig();
        $this->killsConfig = new Config($this->getDataFolder() . "data/kills.json", Config::JSON);
        Server::getInstance()->loadlevel($this->getConfig()->getNested("soup.world"));
        Server::getInstance()->loadlevel($this->getConfig()->getNested("nodebuff.world"));
        Server::getInstance()->loadlevel($this->getConfig()->getNested("gapple.world"));
        # Oh... Just don't touch if it works, please, i dont't want to do this again...
        $this->soupArena = Server::getInstance()->getLevelByName($this->getConfig()->getNested("soup.world"));
        $this->nodebuffArena = Server::getInstance()->getLevelByName($this->getConfig()->getNested("nodebuff.world"));
        $this->gappleArena = Server::getInstance()->getLevelByName($this->getConfig()->getNested("gapple.world"));
        $this->soupX = (int) $this->getConfig()->getNested("soup.coordinates.spawn.x");
        $this->soupY = (int) $this->getConfig()->getNested("soup.coordinates.spawn.y");
        $this->soupZ = (int) $this->getConfig()->getNested("soup.coordinates.spawn.z");
        $this->nodebuffX = (int) $this->getConfig()->getNested("nodebuff.coordinates.spawn.x");
        $this->nodebuffY = (int) $this->getConfig()->getNested("nodebuff.coordinates.spawn.y");
        $this->nodebuffZ = (int) $this->getConfig()->getNested("nodebuff.coordinates.spawn.z");
        $this->gappleX = (int) $this->getConfig()->getNested("gapple.coordinates.spawn.x");
        $this->gappleY = (int) $this->getConfig()->getNested("gapple.coordinates.spawn.y");
        $this->gappleZ = (int) $this->getConfig()->getNested("gapple.coordinates.spawn.z");
        $this->soupSpectatorX = (int) $this->getConfig()->getNested("soup.coordinates.spectator.x");
        $this->soupSpectatorY = (int) $this->getConfig()->getNested("soup.coordinates.spectator.y");
        $this->soupSpectatorZ = (int) $this->getConfig()->getNested("soup.coordinates.spectator.z");
        $this->nodebuffSpectatorX = (int) $this->getConfig()->getNested("nodebuff.coordinates.spectator.x");
        $this->nodebuffSpectatorY = (int) $this->getConfig()->getNested("nodebuff.coordinates.spectator.y");
        $this->nodebuffSpectatorZ = (int) $this->getConfig()->getNested("nodebuff.coordinates.spectator.z");
        $this->gappleSpectatorX = (int) $this->getConfig()->getNested("gapple.coordinates.spectator.x");
        $this->gappleSpectatorY = (int) $this->getConfig()->getNested("gapple.coordinates.spectator.y");
        $this->gappleSpectatorZ = (int) $this->getConfig()->getNested("gapple.coordinates.spectator.z");
    }

    public function killsConfig() {
        # This function will return kills config (kills.json)
        $config = $this->killsConfig;
        return $config;
    }

    public function saveData() {
        # Saving kills data
        $killsConfig = $this->killsConfig();
        $killsConfig->save();
    }

    public function getKills(Player $player) {
        # This function will return player's kills
        $config = $this->killsConfig();
        $name = strtolower($player->getName());
        if(!($config->exists($name))) {
            $kills = 0;
        } else {
            $kills = $config->get($name);
        }
        return $kills;
    }

    public function addKill(Player $player) {
        # This function will add 1 kill to player
        $config = $this->killsConfig();
        $name = strtolower($player->getName());
        $value = $this->getKills($player) + 1;
        $config->set($name, $value);
        $config->save();
    }

    public function setPlayerArenaSettings(Player $player) {
        # This function will set player's settings
        $player->setGamemode(2);
        $player->setMaxHealth(20);
        $player->setHealth(20);
        $player->setFood(20);
        $player->setAllowFlight(false);
        $player->getInventory()->clearAll();
        $player->removeAllEffects();
        $player->extinguish();
        $player->setSprinting(false);
    }

    public function joinArena(Player $player, string $mode) {
        if(!(in_array($mode, $this->modes))) {
            return $player->sendMessage("Unknown mode");
        }
        # Player settings
        $this->setPlayerArenaSettings($player);
        switch($mode) {
            case "soup":
                # Soup kit
                $helmet = Item::get(306, 0, 1); // Iron Helmet
                $chestplate = Item::get(307, 0, 1); // Iron Chestplate
                $leggings = Item::get(308, 0, 1); // Iron Leggings
                $boots = Item::get(309, 0, 1); // Iron Boots
                $sword = Item::get(272, 0, 1); // Stone Sword
                $steak = Item::get(364, 0, 16); // Steak
                $soup = Item::get(282, 0, 16); // Mushroom Stew
                # Giving kit
                $inventory = $player->getInventory();
                $inventory->setHelmet($helmet);
                $inventory->setChestplate($chestplate);
                $inventory->setLeggings($leggings);
                $inventory->setBoots($boots);
                $inventory->addItem($sword);
                $inventory->addItem($steak);
                $inventory->addItem($soup);
                # Teleporting to arena
                if($player->getLevel() !== $this->soupArena) {
                    $player->teleport(Position::fromObject(new Vector3($this->soupX, $this->soupY, $this->soupZ), $this->soupArena));
                } else {
                    $player->teleport(new Vector3($this->soupX, $this->soupY, $this->soupZ));
                }
                break;
            case "nodebuff":
                # Nodebuff kit
                $helmet = Item::get(310, 0, 1); // Diamond Helmet
                $chestplate = Item::get(311, 0, 1); // Diamond Chestplate
                $leggings = Item::get(312, 0, 1); // Diamond Leggings
                $boots = Item::get(313, 0, 1); // Diamond Boots
                $sword = Item::get(276, 0, 1); // Diamond Sword
                $steak = Item::get(364, 0, 16); // Steak
                $potion = Item::get(438, 22, 34); // Healing Potion II
                # Giving kit
                $inventory = $player->getInventory();
                $inventory->setHelmet($helmet);
                $inventory->setChestplate($chestplate);
                $inventory->setLeggings($leggings);
                $inventory->setBoots($boots);
                $inventory->addItem($sword);
                $inventory->addItem($steak);
                $inventory->addItem($potion);
                # Nodebuff effects
                $speed = Effect::getEffect(1); // Speed effect
                $speed->setAmplifier(0);
                $speed->setDuration(2147483648);
                $speed->setVisible(false);
                $player->addEffect($speed);
                # Teleporting to arena
                if($player->getLevel() !== $this->nodebuffArena) {
                    $player->teleport(Position::fromObject(new Vector3($this->nodebuffX, $this->nodebuffY, $this->nodebuffZ), $this->nodebuffArena));
                } else {
                    $player->teleport(new Vector3($this->nodebuffX, $this->nodebuffY, $this->nodebuffZ));
                }
                break;
            case "gapple":
                # Gapple kit
                $helmet = Item::get(310, 0, 1); // Diamond Helmet
                $chestplate = Item::get(311, 0, 1); // Diamond Chestplate
                $leggings = Item::get(312, 0, 1); // Diamond Leggings
                $boots = Item::get(313, 0, 1); // Diamond Boots
                $sword = Item::get(276, 0, 1); // Diamond Sword
                $goldenapple = Item::get(322, 0, 8); // Golden Apple
                # Giving kit
                $inventory = $player->getInventory();
                $inventory->setHelmet($helmet);
                $inventory->setChestplate($chestplate);
                $inventory->setLeggings($leggings);
                $inventory->setBoots($boots);
                $inventory->addItem($sword);
                $inventory->addItem($goldenapple);
                # Teleporting to arena
                if($player->getLevel() !== $this->gappleArena) {
                    $player->teleport(Position::fromObject(new Vector3($this->gappleX, $this->gappleY, $this->gappleZ), $this->gappleArena));
                } else {
                    $player->teleport(new Vector3($this->gappleX, $this->gappleY, $this->gappleZ));
                }
                break;
        }
        # Sound effect
        $player->getLevel()->addSound(new EndermanTeleportSound($player));
    }

    public function setSpectatorSettings(Player $player) {
        # Player settings
        switch($player->getLevel()) {
            case $this->soupArena:
                $player->teleport(new Vector3($this->soupSpectatorX, $this->soupSpectatorY, $this->soupSpectatorZ));
                break;
            case $this->nodebuffArena:
                $player->teleport(new Vector3($this->nodebuffSpectatorX, $this->nodebuffSpectatorY, $this->nodebuffSpectatorZ));
                break;
            case $this->gappleArena:
                $player->teleport(new Vector3($this->gappleSpectatorX, $this->gappleSpectatorY, $this->gappleSpectatorZ));
                break;
        }
        $this->setPlayerArenaSettings($player);
        $invisibility = Effect::getEffect(14); // Invisibility effect
        $invisibility->setAmplifier(0);
        $invisibility->setDuration(2147483648);
        $invisibility->setVisible(false);
        $player->addEffect($invisibility);
        # Giving menu items
        $inventory = $player->getInventory();
        $inventory->addItem(Item::get(388)->setCustomName($this->getConfig()->getNested("items.respawn")));
        $inventory->addItem(Item::get(102)->setCustomName("§0"));
        $inventory->addItem(Item::get(102)->setCustomName("§1"));
        $inventory->addItem(Item::get(102)->setCustomName("§2"));
        $inventory->addItem(Item::get(102)->setCustomName("§3"));
        $inventory->addItem(Item::get(102)->setCustomName("§4"));
        $inventory->addItem(Item::get(102)->setCustomName("§5"));
        $inventory->addItem(Item::get(102)->setCustomName("§6"));
        $inventory->addItem(Item::get(351, 1)->setCustomName($this->getConfig()->getNested("items.quit")));
        # Sound effect
        $player->getLevel()->addSound(new ExplodeSound($player));
    }

    public function arenaRefill(Player $player, string $mode) {
        if(!(in_array($mode, $this->modes))) {
            return $player->sendMessage("Unknown mode");
        }
        # Player settings
        $this->setPlayerArenaSettings($player);
        switch($mode) {
            case "soup":
                # Soup kit
                $helmet = Item::get(306, 0, 1); // Iron Helmet
                $chestplate = Item::get(307, 0, 1); // Iron Chestplate
                $leggings = Item::get(308, 0, 1); // Iron Leggings
                $boots = Item::get(309, 0, 1); // Iron Boots
                $sword = Item::get(272, 0, 1); // Stone Sword
                $steak = Item::get(364, 0, 16); // Steak
                $soup = Item::get(282, 0, 16); // Mushroom Stew
                # Giving kit
                $inventory = $player->getInventory();
                $inventory->setHelmet($helmet);
                $inventory->setChestplate($chestplate);
                $inventory->setLeggings($leggings);
                $inventory->setBoots($boots);
                $inventory->addItem($sword);
                $inventory->addItem($steak);
                $inventory->addItem($soup);
                break;
            case "nodebuff":
                # Nodebuff kit
                $helmet = Item::get(310, 0, 1); // Diamond Helmet
                $chestplate = Item::get(311, 0, 1); // Diamond Chestplate
                $leggings = Item::get(312, 0, 1); // Diamond Leggings
                $boots = Item::get(313, 0, 1); // Diamond Boots
                $sword = Item::get(276, 0, 1); // Diamond Sword
                $steak = Item::get(364, 0, 16); // Steak
                $potion = Item::get(438, 22, 34); // Healing Potion II
                # Giving kit
                $inventory = $player->getInventory();
                $inventory->setHelmet($helmet);
                $inventory->setChestplate($chestplate);
                $inventory->setLeggings($leggings);
                $inventory->setBoots($boots);
                $inventory->addItem($sword);
                $inventory->addItem($steak);
                $inventory->addItem($potion);
                # Nodebuff effects
                $speed = Effect::getEffect(1); // Speed effect
                $speed->setAmplifier(0);
                $speed->setDuration(2147483648);
                $speed->setVisible(false);
                $player->addEffect($speed);
                break;
            case "gapple":
                # Gapple kit
                $helmet = Item::get(310, 0, 1); // Diamond Helmet
                $chestplate = Item::get(311, 0, 1); // Diamond Chestplate
                $leggings = Item::get(312, 0, 1); // Diamond Leggings
                $boots = Item::get(313, 0, 1); // Diamond Boots
                $sword = Item::get(276, 0, 1); // Diamond Sword
                $goldenapple = Item::get(322, 0, 8); // Golden Apple
                # Giving kit
                $inventory = $player->getInventory();
                $inventory->setHelmet($helmet);
                $inventory->setChestplate($chestplate);
                $inventory->setLeggings($leggings);
                $inventory->setBoots($boots);
                $inventory->addItem($sword);
                $inventory->addItem($goldenapple);
                break;
        }
        # Sound effect
        $player->getLevel()->addSound(new AnvilFallSound($player));
    }

    public function quitArena(Player $player) {
        # Player settings
        $player->removeAllEffects();
        $player->setGamemode(2);
        $player->setHealth(1);
        $player->setMaxHealth(1);
        $player->setFood(20);
        $menu = Server::getInstance()->getPluginManager()->getPlugin("AntraliaMenu");
        if($menu !== null) {
            $menu->setMenuItems($player);
        }
        $player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
    }

    public function countSoups(Player $player) {
        $soups = 0;
        foreach($player->getInventory()->getContents() as $item) {
            if($item->getId() == 282 && $item->getDamage() == 0) {
                $soups++;
            }
        }
        return $soups;
    }

    public function countPots(Player $player) {
        $pots = 0;
        foreach($player->getInventory()->getContents() as $item) {
            if($item->getId() == 438 && $item->getDamage() == 22) {
                $pots++;
            }
        }
        return $pots;
    }

    public function sendArenaBar(Player $player, string $mode) {
        if(!(in_array($mode, $this->modes))) {
            return $player->sendMessage("Unknown mode");
        }
        $lines = str_repeat("\n", 3);
        $soups = $this->countSoups($player);
        $pots = $this->countPots($player);
        $ping = $player->getPing();
        switch($mode) {
            case "soup":
                $player->sendTip($lines . str_replace(["{SOUPS}", "{PING}"], [$soups, $ping], $this->getConfig()->getNested("soup.bar")));
                break;
            case "nodebuff":
                $player->sendTip($lines . str_replace(["{POTS}", "{PING}"], [$pots, $ping], $this->getConfig()->getNested("nodebuff.bar")));
                break;
            case "gapple":
                $player->sendTip($lines . str_replace("{PING}", $ping, $this->getConfig()->getNested("gapple.bar")));
                break;
        }
    }

    public function onDisable() {
        $this->getLogger()->info("Saving data...");
        $this->saveData();
    }
}
# Wow, this whole hell is ended...