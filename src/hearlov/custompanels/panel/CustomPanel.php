<?php

namespace hearlov\custompanels\panel;

use hearlov\custompanels\panel\type\{PanelType, CustomInventory};
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;

class CustomPanel{

    public readonly string $name;
    private PanelType $type;
    public readonly string $command;
    public readonly string $permission;
    public readonly string $description;
    public readonly bool $readonly;
    private array $opencommands;
    private array $closecommands;
    private Item $empty;

    private CustomInventory $inventory;

    public function __construct(
        string $name,
        string $type,
        string $command,
        string $permission,
        string $description,
        string $readonly,
        array $panel_commands,
        string $empty,
        array $invargs
    ){
        $this->name = $name;
        $this->type = new PanelType($type);
        $this->command = $command;
        $this->permission = $permission;
        $this->description = $description;
        $this->readonly = $readonly;
        $this->opencommands = $panel_commands["open"] ?? [];
        $this->closecommands = $panel_commands["close"] ?? [];

        $item = StringToItemParser::getInstance()->parse($empty);
        if($item instanceof Item) $this->empty = $item;

        $this->inventory = new CustomInventory($invargs, $this->type, $this->empty ?? VanillaItems::AIR());
    }

    /**
     * @return Item|null
     */
    public function getEmpty(): ?Item
    {
        return $this->empty;
    }

    /**
     * @return PanelType
     */
    public function getType(): PanelType
    {
        return $this->type;
    }

    /**
     * @return CustomInventory
     */
    public function getInventory(): CustomInventory
    {
        return $this->inventory;
    }

    /**
     * @return array
     */
    public function getOpenCommands(): array
    {
        return $this->opencommands;
    }

    /**
     * @return array
     */
    public function getCloseCommands(): array
    {
        return $this->closecommands;
    }

}