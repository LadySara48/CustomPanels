<?php

namespace hearlov\custompanels\manager;

use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;

class InventoryDataManager{

    private static Config $config;
    private static array $datas;

    public static function setup(PluginBase $plugin){
        self::$config = new Config($plugin->getDataFolder() . "datas.yml", Config::YAML);
        self::$datas = array_flip(array_map(fn($i): string => "[".$i."]",array_flip(self::$config->getAll())));
    }

    public static function setData($conx, $context){
        self::$config->set($conx, $context);
        self::$datas["[".$conx."]"] = $context;
        self::$config->save();
    }

    public static function allDatas(): ?array{
        return self::$datas;
    }

    public static function getData($conx): ?String{
        if(self::$config->get($conx) == null) return null;
        return self::$config->get($conx);
    }

}