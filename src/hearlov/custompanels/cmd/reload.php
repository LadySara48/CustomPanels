<?php

namespace hearlov\custompanels\cmd;

use pocketmine\command\CommandSender;
use hearlov\custompanels\CustomPanels;
use pocketmine\plugin\PluginOwned;
use pocketmine\command\Command;

class reload extends Command implements PluginOwned
{

    private CustomPanels $plugin;

    public function __construct(CustomPanels $plugin)
    {
        parent::__construct("cpreload");
        $this->plugin = $plugin;
        $this->setDescription("CustomPanels Plugini Refreshes Panels");
        $this->setPermissionMessage("CustomPanels Uses custompanels.settings perm. you must have this");
        $this->setPermission("custompanels.settings");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        $this->plugin->reloadState(0, $sender);
        $sender->sendMessage($this->plugin::PREFIX . " Server Panels Restarted.");
    }

    public function getOwningPlugin(): CustomPanels{
        return $this->plugin;
    }

}