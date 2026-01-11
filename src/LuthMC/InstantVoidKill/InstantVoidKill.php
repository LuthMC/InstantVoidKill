<?php

namespace LuthMC\InstantVoidKill;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class InstantVoidKill extends PluginBase implements Listener {

    /** @var Config */
    private $config;

    /** @var array */
    private $toggledPlayers = [];

    public function onEnable(): void {
        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            "world_settings" => [
                "world" => [
                    "void_y_level" => 0
                ]
            ],
            "teleport_instead_of_kill" => false,
            "teleport_message" => "You were teleported to safety!",
            "spawn_location" => [
                "x" => 0,
                "y" => 100,
                "z" => 0,
                "world" => "world"
            ]
        ]);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $worldName = $player->getWorld()->getFolderName();
        if (isset($this->toggledPlayers[$player->getName()])) {
            return;
        }

        $voidYLevel = $this->config->get("world_settings")[$worldName]["void_y_level"] ?? 0;
        if ($player->getPosition()->y < $voidYLevel) {
            if ($this->config->get("teleport_instead_of_kill", false)) {
                $spawnLocation = $this->config->get("spawn_location");
                $world = $this->getServer()->getWorldManager()->getWorldByName($spawnLocation["world"]);
                if ($world !== null) {
                    $player->teleport(new Position(
                        $spawnLocation["x"],
                        $spawnLocation["y"],
                        $spawnLocation["z"],
                        $world
                    ));
                    $player->sendMessage($this->config->get("teleport_message", "You were teleported to safety!"));
                }
            } else {
                $player->kill();
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "ivk") {
            switch ($args[0] ?? "") {
                case "set":
                    if (!$sender->hasPermission("instantvoidkill.use")) {
                        $sender->sendMessage("You do not have permission to use this command.");
                        return false;
                    }

                    if (!isset($args[1]) || !isset($args[2])) {
                        $sender->sendMessage("Usage: /ivk set <y-level> <world_name>");
                        return false;
                    }

                    $yLevel = (int)$args[1];
                    $worldName = $args[2];

                    if (!is_numeric($yLevel)) {
                        $sender->sendMessage("Y level must be a number!");
                        return false;
                    }

                    $worldSettings = $this->config->get("world_settings", []);
                    $worldSettings[$worldName] = ["void_y_level" => $yLevel];
                    $this->config->set("world_settings", $worldSettings);
                    $this->config->save();

                    $sender->sendMessage("Void Y level for '$worldName' set to $yLevel.");
                    return true;

                case "toggle":
                    if (!$sender->hasPermission("instantvoidkill.toggle")) {
                        $sender->sendMessage("You do not have permission to use this command.");
                        return false;
                    }

                    if (!$sender instanceof Player) {
                        $sender->sendMessage("This command can only be used in-game.");
                        return false;
                    }

                    $playerName = $sender->getName();
                    if (isset($this->toggledPlayers[$playerName])) {
                        unset($this->toggledPlayers[$playerName]);
                        $sender->sendMessage("InstantVoidKill Enabled!");
                    } else {
                        $this->toggledPlayers[$playerName] = true;
                        $sender->sendMessage("InstantVoidKill Disabled!");
                    }
                    return true;

                case "settp":
                    if (!$sender->hasPermission("instantvoidkill.settp")) {
                        $sender->sendMessage("You do not have permission to use this command.");
                        return false;
                    }

                    if (!$sender instanceof Player) {
                        $sender->sendMessage("This command can only be used in-game.");
                        return false;
                    }

                    $location = $sender->getPosition();
                    $this->config->set("spawn_location", [
                        "x" => $location->getX(),
                        "y" => $location->getY(),
                        "z" => $location->getZ(),
                        "world" => $location->getWorld()->getFolderName()
                    ]);
                    $this->config->set("teleport_instead_of_kill", true);
                    $this->config->save();

                    $sender->sendMessage("Spawn location set to your current position.");
                    return true;

                default:
                    $sender->sendMessage("Usage: /ivk <set|toggle|settp>");
                    return false;
            }
        }
        return false;
    }
}
