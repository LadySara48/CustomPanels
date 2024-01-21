<?php

namespace hearlov\custompanels\manager;



use hearlov\custompanels\CustomPanels;
use hearlov\custompanels\libs\muqsit\invmenu\InvMenu;
use hearlov\custompanels\libs\muqsit\invmenu\transaction\InvMenuTransaction;
use hearlov\custompanels\libs\muqsit\invmenu\transaction\InvMenuTransactionResult;
use hearlov\custompanels\libs\muqsit\invmenu\type\InvMenuTypeIds;
use hearlov\custompanels\manager\AgFactory as AF;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;

class OpenPanel{

    private static CustomPanels $plugin;
    private static bool $usedatas;

    public static function setup(CustomPanels $plugin, bool $usedatas){
        self::$plugin = $plugin;
        self::$usedatas = $usedatas;
    }

    private static function getEditedMenu(Player $player, SimpleInventory $inv, InvMenu $invm): SimpleInventory{
        $newenv = new SimpleInventory($inv->getSize());
        $newenv->setContents($inv->getContents());
        foreach($newenv->getContents() as $index => $item){
            if($item->hasCustomName()){
                $nwname = AF::TextGenerate(self::$plugin->getServer(), $player, $item, $item->getCustomname(), $invm);
                $newenv->setItem($index, $item->setCustomName(self::$usedatas ? AF::getDataStatic($nwname) : $nwname));
            }
        }
        return $newenv;
    }

    public static function command(Player $player, string $command){
        if(!in_array($command, array_keys(self::$plugin->getPanels()))) return;
        $panel = self::$plugin->getPanel($command);
        if(!isset($panel)) return;
        $inventory = $panel["inventory"] ?? null;
        if(!$inventory instanceof SimpleInventory) return;

        $inv = InvMenu::create($inventory->getSize() == 54 ? InvMenuTypeIds::TYPE_DOUBLE_CHEST : InvMenuTypeIds::TYPE_CHEST);
        $inv->setName($panel["name"]);
        $envanter = new SimpleInventory($inventory->getSize());
        $envanter->setContents(self::getEditedMenu($player, $inventory, $inv)->getContents());
        $inv->setInventory($envanter);
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
                            $inv->getInventory()->setContents(self::getEditedMenu($transaction->getPlayer(), $inventory, $inv)->getContents());
                            if(isset($panel["opencmd"])) self::openCMD($panel["opencmd"], $transaction->getPlayer(), $inv);
                        } else {
                            self::$plugin->sendCommandInItem($transaction->getPlayer(), $transaction->getOut(), $command, $inv);
                        }
                    }

                }

                if(!$instance) return $transaction->discard(); else return $transaction->continue();
            });
        }
        if(isset($panel["closecmd"])){
            $inv->setInventoryCloseListener(function (Player $player, SimpleInventory $inventory) use ($panel, $inv): void{
                foreach($panel["closecmd"] as $cmd){
                    self::$plugin->sendCommandInItem($player, $player->getInventory()->getItemInHand(), $cmd, $inv);
                }
            });
        }
        if(isset($panel["opencmd"])) self::openCMD($panel["opencmd"], $player, $inv);
        $inv->send($player);

    }

    private static function openCMD(array $args, Player $player, InvMenu &$inv){
        foreach($args as $cmd){
            self::$plugin->sendCommandInItem($player, $player->getInventory()->getItemInHand(), $cmd, $inv);
        }
    }

}