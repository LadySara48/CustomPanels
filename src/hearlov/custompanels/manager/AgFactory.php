<?php

namespace hearlov\custompanels\manager;

use hearlov\custompanels\CustomPanels;
use hearlov\custompanels\libs\muqsit\invmenu\InvMenu;
use hearlov\custompanels\utils\MoneyUtil as MU;
use pocketmine\item\StringToItemParser as SI;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;

class AgFactory{

    const REPLACER = [ //SIRALAMAYA GORE LISTE
        "{player}", "{item_name}", "{item_vanilla_name}",
        "{player_handitem_name}", "{player_handitem_vanilla_name}",
        "{server_online}", "{server_max}", "{server_motd}",
        "{player_handitem_meta}",
        "{random_player}",
        "{money}", "{level}",
        "{x}", "{y}", "{z}", "{world}",
        "{language}", "{time}", "{year}", "{month}", "{day}",
        "{hour}", "{minute}", "{second}"
    ];

    public static function TextGenerate(Server $server, Player $player, Item $item, String $cmd, InvMenu $inv){
        return str_replace(
            self::REPLACER,
            [ //SIRALAMAYA GORE LISTE
                $player->getName(), $item->getName(), $item->getVanillaName(),
                $player->getInventory()->getItemInHand()->getName(), $player->getInventory()->getItemInHand()->getVanillaName(),
                count($server->getOnlinePlayers()), $server->getMaxPlayers(), $server->getMotd(),
                SI::getInstance()->lookupAliases($player->getInventory()->getItemInHand())[0],
                $server->getOnlinePlayers()[array_rand($server->getOnlinePlayers())]->getName(),
                MU::getMoney($player->getName()), $player->getXpManager()->getXpLevel(),
                $player->getPosition()->x, $player->getPosition()->y, $player->getPosition()->z, $player->getPosition()->getWorld()->getFolderName(),
                $player->getLocale(), date("Y/m/d"), date("Y"), date("m"), date("d"),
                date("H"), date("i"), date("s")
            ],
            $cmd);
    }

    public static function getDataStatic(String $conx): ?String{
        if(str_contains($conx, "[") && str_contains($conx, "]")){
            return str_replace(
                array_keys(InventoryDataManager::allDatas()),
                array_values(InventoryDataManager::allDatas()),
            $conx);
        }
        return $conx;
    }

}