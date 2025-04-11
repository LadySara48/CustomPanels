<?php

namespace hearlov\custompanels\panel\type;

use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;

class CustomInventory{

    private PanelType $type;
    private SimpleInventory $inventory;
    private array $invargs;
    private array $commands;
    private array $readonly;

    public function __construct(array $invargs, PanelType $type, Item $empty){
        $this->type = $type;
        $this->invargs = $invargs;
        $this->inventory = $this->arrToInventory($invargs, $empty);
    }

    private function arrToInventory(array $arg, Item $empty): ?SimpleInventory{
        $inventory = new SimpleInventory($this->type->getTypeSize());
        if($empty->getVanillaName() !== "Air"){
            for($i = 0; $i < $this->type->getTypeSize(); $i++){
                $inventory->setItem($i, $empty);
            }
        }
        foreach($arg as $index => $item){
            if(!is_numeric($index)) continue;
            if($index >= $this->type->getTypeSize()) continue;
            $itm = StringToItemParser::getInstance()->parse($item["meta"] ?? "air");
            if($itm === null) continue;
            if($itm->getVanillaName() == "Air"){
                $inventory->clear($index);
            }else{
                $itm->setCustomName($item["name"] ?? "");
                $itm->setLore($item["lore"] ?? []);
                $inventory->setItem($index, $itm->setCount($item["count"] ?? 1));
            }
            if(isset($item["commands"])) $this->commands[$index] = $item["commands"];
            if(isset($item["readonly"]) && !$item["readonly"]) $this->readonly[$index] = $item["readonly"];
        }
        return $inventory;
    }

    public function getCommands(): array{
        return $this->commands;
    }

    public function getCommand(int $index): ?array{
        return $this->commands[$index] ?? null;
    }

    public function allReadonly(): array{
        return $this->readonly;
    }

    public function getReadonly(int $index): bool{
        return $this->readonly[$index] ?? true; //Def True
    }

    public function getInventory(): SimpleInventory{
        return $this->inventory;
    }

    public function getInventoryArgs(): array{
        return $this->invargs;
    }

}