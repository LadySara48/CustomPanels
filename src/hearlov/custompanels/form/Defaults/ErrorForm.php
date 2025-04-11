<?php

namespace hearlov\custompanels\form\error;

use pocketmine\form\Form;
use pocketmine\player\Player;

class ErrorForm implements Form{

    private $backstable = false;

    public function __construct(
        public Player $player,
        public string $title,
        public string $content,
        public ?Form $back = null
    ){
        if($back !== null) $this->backstable = true;
    }

    public function handleResponse(Player $player, $data): void
    {
        if(!isset($data)) return;
        if(!$data) return;
        if($this->backstable){
            $player->sendForm($this->back);
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            "type" => "modal",
            "title" => $this->title,
            "content" => $this->content,
            "button1" => "Geri",
            "button2" => "Kapat"
        ];
    }


}