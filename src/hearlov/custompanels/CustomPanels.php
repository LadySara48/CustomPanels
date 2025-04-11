<?php

namespace hearlov\custompanels;

use hearlov\custompanels\cmd\{reload, cp};
use hearlov\custompanels\{utils\CommandUtil as CU,
    utils\MoneyUtil as MU,
    manager\AgFactory as AF,
    manager\OpenPanel,
    manager\InventoryDataManager,
    utils\MoneyUtil};
use pocketmine\{command\Command,
    command\CommandSender,
    console\ConsoleCommandSender,
    item\Item,
    item\StringToItemParser,
    player\Player,
    plugin\PluginBase,
    scheduler\ClosureTask,
    utils\Config,
    world\Position};

use hearlov\custompanels\libs\muqsit\invmenu\{InvMenu, InvMenuHandler};
use hearlov\custompanels\panel\{CustomPanel, Panels};


Class CustomPanels extends PluginBase{

    public const PREFIX = "§7[§6CP§7]";

	//Config
	private Config $config;
    private array $commands = [];
    private bool $usedata = true;

	private static ?CustomPanels $instance = null;

	public function onEnable(): void{
        if(!file_exists($this->getDataFolder() . "config.yml")){
            $this->saveResource("Panel.yml");
        }
        $this->saveDefaultConfig();
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        Panels::setup($this);
        $this->reloadState(1);
        $this->usedata = $this->config->get("use-datas") ?? true;

        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        OpenPanel::setup($this, $this->usedata);
        if($this->usedata) InventoryDataManager::setup($this);
        MU::setup($this->config->get("EconomyPlugin"));
	}

	public function onLoad(): void{
		self::$instance = $this;
	}
	
	public static function getInstance(): CustomPanels {
		return self::$instance;
	}
	
	public function getConfig(): Config{
		return $this->config;
    }

    //START PANEL STR

    public function sendCommandInItem(Player $player, Item $item, String $cmd, InvMenu &$inv): void{
        $cmd = AF::TextGenerate($this->getServer(), $player, $item, $cmd, $inv);
        $cmd = AF::getInventorySlots($cmd, $inv);
        if($this->usedata) $cmd = AF::getDataStatic($cmd);
        if(str_starts_with($cmd, "cmd=")){
            $command = substr($cmd, 4);
            $this->getServer()->dispatchCommand(new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()), "$command");
        }elseif(str_starts_with($cmd, "msg=")){
            $message = substr($cmd, 4);
            $player->sendMessage($message);
        }elseif(str_starts_with($cmd, "rca=")){
            $command = substr($cmd, 4);
            $this->getServer()->dispatchCommand($player, "$command");
        }elseif(str_starts_with($cmd, "tms=")){
            $command = substr($cmd, 4);
            $time = explode(" ", $command)[0];
            if(!is_numeric($time)) return;
            if($time < 50){
                $this->getLogger()->warning("Duration commands support a minimum of 50 ms and must be multiples of 50.");
                return;
            }
            $command = substr($command, (strlen($time) + 1));
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $item, $command, $inv){
                $this->sendCommandInItem($player, $item, $command, $inv);
            }), (int)($time / 50));
        }elseif(str_starts_with($cmd, "tp=")){
            $command = substr($cmd, 3);
            $pos = explode(" ", $command);
            if(count($pos) < 3) return;
            if(!(is_numeric($pos[0]) && is_numeric($pos[1]) && is_numeric($pos[2]))) return;
            if(isset($pos[3]) && $this->getServer()->getWorldManager()->isWorldGenerated($pos[3]) && !$this->getServer()->getWorldManager()->isWorldLoaded($pos[3])){ $this->getServer()->getWorldManager()->loadWorld($pos[3]); }
            $player->teleport(new Position($pos[0], $pos[1], $pos[2], isset($pos[3]) ? $this->getServer()->getWorldManager()->getWorldByName($pos[3]) : null));
        }elseif(str_starts_with($cmd, "open=")){
            $command = substr($cmd, 5);
            if(Panels::getPanel($command) === null) return;
            $this->sendCommandInItem($player, $item, "rca=custompanels:$command", $inv);
        }elseif(str_starts_with($cmd, "give=")){
            $command = substr($cmd, 5);
            $arg = explode(" ", $command);
            if(count($arg) < 1) return;
            $item = StringToItemParser::getInstance()->parse($arg[0]);
            if($item === null || $arg[0] == "air") return;
            if(isset($arg[1]) && is_numeric($arg[1]) && (1 <= $arg[1] && 64 >= $arg[1])) $item->setCount($arg[1]);
            if($player->getInventory()->canAddItem($item)) $player->getInventory()->addItem($item);
        }elseif(str_starts_with($cmd, "set_item=")){
            $command = substr($cmd, 9);
            $arg = explode(" ", $command);
            if(count($arg) < 1) return;
            $item = StringToItemParser::getInstance()->parse($arg[0]);
            if($item === null || $item->getVanillaName() == "Air") return;
            if(isset($arg[1]) && is_numeric($arg[1]) && (1 <= $arg[1] && 64 >= $arg[1])) $item->setCount($arg[1]);
            if(isset($arg[2]) && is_numeric($arg[2]) && $inv->getInventory()->getSize() > $arg[2]) $inv->getInventory()->setItem($arg[2], $item);
        }elseif(str_starts_with($cmd, "set_data=")){
            if(!$this->usedata) return;
            $command = substr($cmd, 9);
            if(count(explode(" ", $command)) < 1) return;
            $spc = ["exp" => explode(" ", $command)[0], "cont" => substr($command, strlen(explode(" ", $command)[0]) + 1)];
            InventoryDataManager::setData($spc["exp"], $spc["cont"]);
        }elseif(str_starts_with($cmd, "buy=")){
            $command = substr($cmd, 4);
            if(count(explode(" ", $command)) < 3) return;
            $exp = explode(" ", $command);
            $item = StringToItemParser::getInstance()->parse($exp[0]);
            if(!(is_numeric($exp[2]) && is_numeric($exp[1]) && $item instanceof Item)) return;
            if(!MoneyUtil::buyItem($player,$exp[2] * 1) && !$player->getInventory()->canAddItem($item->setCount($exp[1]))) return;
            $player->getInventory()->addItem($item->setCount($exp[1]));
        }elseif(str_starts_with($cmd, "buycmd=")){
            $command = substr($cmd, 7);
            $exp = explode(" ", $command);
            if(count($exp) < 2) return;
            if(!is_numeric($exp[0])) return;
            if(!MoneyUtil::buyItem($player,$exp[0] * 1)) return;
            $command = substr($command, strlen($exp[0]) + 1);
            $this->sendCommandInItem($player, $item, $command, $inv);
        }elseif(str_starts_with($cmd, "sell=")){
            $command = substr($cmd, 5);
            $exp = explode(" ", $command);
            $item = StringToItemParser::getInstance()->parse($exp[0]);
            if(!(is_numeric($exp[2]) && is_numeric($exp[1]) && $item instanceof Item)) return;
            $item->setCount($exp[1]);
            if(!MoneyUtil::sellItem($player,$exp[2] * 1, $item)) return;
            $player->getInventory()->removeItem($item);
        }elseif(str_starts_with($cmd, "sellcmd=")){
            $command = substr($cmd, 8);
            $exp = explode(" ", $command);
            if (count($exp) < 3) return;
            if(!is_numeric($exp[1])) return;
            $item = StringToItemParser::getInstance()->parse($exp[0]);
            $item->setCount($exp[1] * 1);
            if(!$item instanceof Item) return;
            if(!$player->getInventory()->contains($item)) return;
            $player->getInventory()->removeItem($item);
            $command = substr($command, strlen($exp[0]) + strlen($exp[1]) + 2);
            $this->sendCommandInItem($player, $item, $command, $inv);
        }elseif(str_starts_with($cmd, "if")){
            /*
             * $command = substr($cmd, 2);
             * COOMING SOON
             */
        }

    }

    //STATE

    public function reloadState(int $state, CommandSender $sender = null){
        if($state == 0) $this->delCommands();
        Panels::reload();
        $files = glob($this->getDataFolder() . '*.yml', GLOB_ERR);
        foreach($files as $file) {
            if(str_ends_with($file, "config.yml") || str_ends_with($file, "datas.yml")) continue;
            $yamldata = new Config($file, Config::YAML);
            $data = $yamldata->get("panel");
            if(!(isset($data["items"]) && isset($data["type"]) && isset($data["name"]) && isset($data["command"]))) continue;
            if(!is_array($data["items"])) continue;
            //A
            if($sender !== null) $sender->sendMessage("§6".$data["command"] . " §3panel is activating...");

                $name = $data["name"];
                $command = $data["command"];
                $type = $data["type"];
                $invarg = $data["items"];
                $desc = $data["description"] ?? "";
                $ro = $data["readonly"] ?? true;
                $perm = $data["permission"] ?? "custompanels.openpanels";
                $pcmd = [];
                if(isset($data["panel-open-commands"])) $pcmd["open"] = $data["panel-open-commands"];
                if(isset($data["panel-close-commands"])) $pcmd["close"] = $data["panel-close-commands"];
                $empty = $data["empty"] ?? "";

                $panel = new CustomPanel($name, $type, $command, $perm, $desc, $ro, $pcmd, $empty, $invarg);
                Panels::register($panel);

            if($sender !== null) $sender->sendMessage("§6".$data["command"] . " §3panel is loaded and has size §3" . count($invarg) . " §3Item has been loaded.");
            //A
        }
        if($sender !== null) $sender->sendMessage("§aPanel Readers processed. Menus are active\n");
        $this->initCommands($state);
        if($state == 0) $this->reloadCommandMap();
    }

    private function reloadCommandMap(){
        foreach($this->getServer()->getOnlinePlayers() as $player){
            $player->getNetworkSession()->syncAvailableCommands();
        }
    }

    private function delCommands(): void{
        foreach($this->commands as $command){
            $this->getServer()->getCommandMap()->unregister($command);
        }
    }

	private function initCommands(int $state): void{
        $commands = [
            
        ];
        if($state == 1){
            $commands = [
                new reload($this),
                new cp($this)
            ];
        }
        foreach(Panels::getPanels() as $panel){
            $command = new CU($this, $panel->command, $panel->description, $panel->permission);
            $commands[] = $command;
            $this->commands[] = $command;
        }

        $this->getServer()->getCommandMap()->registerAll("CustomPanels", $commands);
    }

}
