<?php

namespace hearlov\custompanels;

use pocketmine\{console\ConsoleCommandSender,
    inventory\SimpleInventory,
    item\Item,
    item\StringToItemParser,
    item\VanillaItems,
    player\Player,
    plugin\PluginBase,
    utils\Config,
    scheduler\ClosureTask,
    world\Position};
use hearlov\custompanels\utils\CommandUtil as CU;
use hearlov\custompanels\utils\MoneyUtil as MU;
use muqsit\invmenu\{InvMenu, transaction\InvMenuTransaction, transaction\InvMenuTransactionResult, type\InvMenuTypeIds, InvMenuHandler};

use hearlov\custompanels\cmd\reload;


Class CustomPanels extends PluginBase{

    public const PREFIX = "§7[§6CP§7]";

	//Config
	private $config;

    //Setup
    private $panels;
    private $commands = [];
	
	private static $instance = null;

	public function onEnable(): void{
        if(!class_exists("muqsit\invmenu\InvMenu")){
            $this->getLogger()->critical("Error, you must have InvMenu Virion to use this plugin. this Plugin disabled");
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (){
                $this->getServer()->getPluginManager()->disablePlugin($this);
            }), 40);
            return;
        }
        if(!file_exists($this->getDataFolder() . "config.yml")){
            $this->saveResource("Panel.yml");
        }
        $this->saveDefaultConfig();
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->reloadState(1);

        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        MU::setup($this->config->get("EconomyPlugin"));
	}

	public function onLoad(): void{
		self::$instance = $this;
	}
	
	public static function getInstance(): CustomPanels {
		return self::$instance;
	}

    public function getPanels(): array{
        return $this->panels;
    }

    /**
     * @param String $panelname
     * @return array|null
     */
    public function getPanel(String $panelname): ?array{
        return $this->panels[$panelname] ?? null;
    }
	
	public function getConfig(): Config{
		return $this->config;
    }

    //START PANEL STR

    public function TextGenerate(Player $player, Item $item, String $cmd){
        return str_replace(
            [ //SIRALAMAYA GORE LISTE
                "{player}", "{item_name}", "{item_vanilla_name}",
                "{player_handitem_name}", "{player_handitem_vanilla_name}",
                "{server_online}", "{server_max}", "{server_motd}",
                "{random_player}",
                "{money}", "{level}",
                "{x}", "{y}", "{z}", "{world}",
                "{language}", "{time}", "{year}", "{month}", "{day}",
                "{hour}", "{minute}", "{second}"
            ],
            [ //SIRALAMAYA GORE LISTE
                $player->getName(), $item->getName(), $item->getVanillaName(),
                $player->getInventory()->getItemInHand()->getName(), $player->getInventory()->getItemInHand()->getVanillaName(),
                count($this->getServer()->getOnlinePlayers()), $this->getServer()->getMaxPlayers(), $this->getServer()->getMotd(),
                $this->getServer()->getOnlinePlayers()[array_rand($this->getServer()->getOnlinePlayers())]->getName(),
                MU::getMoney($player->getName()), $player->getXpManager()->getXpLevel(),
                $player->getPosition()->x, $player->getPosition()->y, $player->getPosition()->z, $player->getPosition()->getWorld()->getFolderName(),
                $player->getLocale(), date("Y/m/d"), date("Y"), date("m"), date("d"),
                date("H"), date("i"), date("s")
            ],
            $cmd);
    }

    public function sendCommandInItem(Player $player, Item $item, String $cmd): void{
        $cmd = $this->TextGenerate($player, $item, $cmd);
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
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $item, $command){
                $this->sendCommandInItem($player, $item, $command);
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
            if($this->getPanel($command) === null) return;
            $this->sendCommandInItem($player, $item, "rca=custompanels:$command");
        }elseif(str_starts_with($cmd, "give=")){
            $command = substr($cmd, 5);
            $arg = explode(" ", $command);
            if(count($arg) < 1) return;
            $item = StringToItemParser::getInstance()->parse($arg[0]);
            if(isset($arg[1]) && is_numeric($arg[1]) && (1 <= $arg[1] && 64 >= $arg[1])) $item->setCount($arg[1]);
            if($item === null) return;
            if($player->getInventory()->canAddItem($item)) $player->getInventory()->addItem($item);
        }
    }

    //STATE


    public function reloadState(int $state){
        $this->panels = [];
        if($state == 0) $this->delCommands();
        $files = glob($this->getDataFolder() . '*.yml', GLOB_ERR);
        $this->getLogger()->alert("Panel Readers are being processed.\n");
        foreach($files as $file) {
            if(str_ends_with($file, "config.php")) continue;
            $yamldata = new Config($file, Config::YAML);
            $data = $yamldata->get("panel");
            if(!(isset($data["items"]) && isset($data["type"]) && isset($data["name"]) && isset($data["command"]))) continue;
            if(!is_array($data["items"])) continue;
            //A
            $this->getLogger()->notice($data["command"] . " panel is activating...");
            $size = $data["type"] == "DOUBLE_CHEST" ? 54 : 27;

            $arr = [];
            $inv = new SimpleInventory($size);
            if(isset($data["empty"])){
                $item = StringToItemParser::getInstance()->parse($data["empty"]) ?? VanillaItems::AIR();
                for($i = 0; $i < $size; $i++){
                    $inv->setItem($i, $item);
                }
            }
            foreach($data["items"] as $index => $item){
                if(!is_numeric($index)) continue;
                $itm = StringToItemParser::getInstance()->parse($item["meta"] ?? "air");
                if($itm === null) continue;
                if($itm->getVanillaName() == "Air"){
                    $inv->clear($index);
                }else {
                    $itm->setCustomName($item["name"] ?? "");
                    $inv->setItem($index, $itm->setCount($item["count"] ?? 1));
                }
                $arr["commands"][$index] = $item["commands"] ?? [];
                $arr["readonly"][$index] = $item["readonly"] ?? true; //Default in True
            }
            $arr["inventory"] = $inv;
            $arr["readonly"]["general"] = $data["readonly"] ?? true; //Default in True
            $arr["description"] = $data["description"] ?? "";
            $arr["permission"] = $data["permission"] ?? "custompanels.openpanels";
            $this->panels[$data["command"]] = $arr;
            $this->panels[$data["command"]]["name"] = $data["name"];
            $this->getLogger()->notice($data["command"] . " panel is loaded and has size $size " . count($arr["commands"]) . "Command has been loaded to 1 item.");
            //A
        }
        $this->getLogger()->alert("Panel Readers processed. Menus are active\n");
        $this->initCommands($state);
        if($state == 0) $this->reloadCommandMap();
    }

    private function getEditedMenu(Player $player, SimpleInventory $inv): SimpleInventory{
        $newenv = new SimpleInventory($inv->getSize());
        $newenv->setContents($inv->getContents());
        foreach($newenv->getContents() as $index => $item){
            if($item->hasCustomName()) $newenv->setItem($index, $item->setCustomName($this->TextGenerate($player, $item, $item->getCustomname())));
        }
        return $newenv;
    }

    public function command(Player $player, string $command){
        if(!in_array($command, array_keys($this->getPanels()))) return;
        $panel = $this->getPanel($command);
        if(!isset($panel)) return;
        $inventory = $panel["inventory"] ?? null;
        if(!$inventory instanceof SimpleInventory) return;

        $envanter = new SimpleInventory($inventory->getSize());
        $envanter->setContents($this->getEditedMenu($player, $inventory)->getContents());
        $inv = InvMenu::create($inventory->getSize() == 54 ? InvMenuTypeIds::TYPE_DOUBLE_CHEST : InvMenuTypeIds::TYPE_CHEST, $envanter);
        foreach($inv->getInventory()->getContents() as $index => $item){ $inv->getInventory()->setItem($index, $item->setCustomName($this->TextGenerate($player, $item, $item->getCustomname()))); }
        $inv->setName($panel["name"]);
        if(isset($panel["commands"])) {
            $inv->setListener(function (InvMenuTransaction $transaction) use ($inv, $panel, $inventory): InvMenuTransactionResult {
                $instance = false;
                $index = $transaction->getAction()->getSlot();
                if((isset($panel["readonly"][$index]) && !$panel["readonly"][$index]) || !$panel["readonly"]["general"]) $instance = true;

                if (isset($panel["commands"]) && in_array($index, array_keys($panel["commands"]))) {

                    $commands = $panel["commands"][$index];
                    foreach ($commands as $command) {
                        if ($command == "close") {
                            $inv->onClose($transaction->getPlayer());
                        } elseif ($command == "reload") {
                            $inv->getInventory()->setContents($this->getEditedMenu($transaction->getPlayer(), $inventory)->getContents());
                        } else {
                            $this->sendCommandInItem($transaction->getPlayer(), $transaction->getOut(), $command);
                        }
                    }

                }

                if(!$instance) return $transaction->discard(); else return $transaction->continue();
            });
        }
        $inv->send($player);

    }

    private function reloadCommandMap(){
        foreach($this->getServer()->getOnlinePlayers() as $player){
            $player->getNetworkSession()->syncAvailableCommands();
        }
    }

    private function delCommands(): void{
        $this->getLogger()->alert("Panel Commands Removing...");
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
            ];
        }
        foreach($this->panels as $key => $arr){
            $command = new CU($this, $key, $arr["description"], $arr["permission"]);
            $commands[] = $command;
            $this->commands[] = $command;
        }

        $this->getServer()->getCommandMap()->registerAll("CustomPanels", $commands);
    }

}
