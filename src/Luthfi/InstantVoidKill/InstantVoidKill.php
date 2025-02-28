<?php

namespace Luthfi\InstantVoidKill;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class InstantVoidKill extends PluginBase implements Listener {

    /** @var Config */
    private $config;

    /** @var int */
    private $voidYLevel;

    public function onEnable(): void {
        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["void_y_level" => 0]);
        $this->voidYLevel = $this->config->get("void_y_level", 0);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        if ($player->getPosition()->y < $this->voidYLevel) {
            $player->kill();
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "ivk") {
            if (!isset($args[0])) {
                $sender->sendMessage("Usage: /ivk set <y-level>");
                return false;
            }

            if ($args[0] === "set") {
                if (!isset($args[1])) {
                    $sender->sendMessage("Usage: /ivk set <y-level>");
                    return false;
                }

                $yLevel = (int)$args[1];
                if (!is_numeric($yLevel)) {
                    $sender->sendMessage("Y level must be a number!");
                    return false;
                }

                $this->voidYLevel = $yLevel;
                $this->config->set("void_y_level", $yLevel);
                $this->config->save();
                $sender->sendMessage("Void Y level set to " . $yLevel);
                return true;
            }
        }
        return false;
    }
}
