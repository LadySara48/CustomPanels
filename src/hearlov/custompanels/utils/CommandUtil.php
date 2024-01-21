<?php

namespace hearlov\custompanels\utils;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use hearlov\custompanels\CustomPanels;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use hearlov\custompanels\manager\OpenPanel;

class CommandUtil extends Command implements PluginOwned{

    public CustomPanels $plugin;

    /**
     * @param string $name
     * @param string $description
     */
    public function __construct(CustomPanels $plugin, string $name, string $description = "", string $perm = "custompanels.openpanels"){
        parent::__construct($name);
        $this->plugin = $plugin;
        $this->setDescription($description);
        $this->setPermission($perm);
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if(!$sender instanceof Player) return false;
        OpenPanel::command($sender, explode(":", $commandLabel)[1] ?? $commandLabel);
        return true;
    }

    public function getOwningPlugin(): CustomPanels{
        return $this->plugin;
    }

}