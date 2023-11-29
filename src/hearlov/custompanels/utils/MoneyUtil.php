<?php

namespace hearlov\custompanels\utils;

use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use onebone\economyapi\EconomyAPI;
use cooldogedev\BedrockEconomy\addon\AddonManager;
class MoneyUtil{

public static $economyplugin;

    public static function setup(String $eco){
        self::$economyplugin = $eco == "EconomyAPI" ? "EconomyAPI" : "BedrockEconomy";
    }

    /**
     * @param String $player
     * @param int $count
     * @return void
     * Para ekler
     */
    public static function addMoney(String $player, int $count){
        if(self::$economyplugin == "EconomyAPI"){
            EconomyAPI::getInstance()->addMoney($player, $count);
        }elseif(self::$economyplugin == "BedrockEconomy"){
            BedrockEconomyAPI::beta()->add($player, $count);
        }
    }

    /**
     * @param String $player
     * @param int $count
     * @return void
     */
    public static function reduceMoney(String $player, int $count){
        if(self::$economyplugin == "EconomyAPI"){
            EconomyAPI::getInstance()->reduceMoney($player, $count);
        }elseif(self::$economyplugin == "BedrockEconomy"){
            BedrockEconomyAPI::beta()->deduct($player, $count);
        }
    }

    /**
     * @param String $player
     * @param int $count
     * @return void
     */
    public static function setMoney(String $player, int $count){
        if(self::$economyplugin == "EconomyAPI"){
            EconomyAPI::getInstance()->setMoney($player, $count);
        }elseif(self::$economyplugin == "BedrockEconomy"){
            BedrockEconomyAPI::beta()->set($player, $count);
        }
    }

    public static function getMoney(String $player): float
    {
        if(self::$economyplugin == "EconomyAPI"){
            return EconomyAPI::getInstance()->myMoney($player);
        }elseif(self::$economyplugin == "BedrockEconomy"){

        }
        return 0;
    }
}