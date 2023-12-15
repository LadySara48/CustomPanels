<?php

declare(strict_types=1);

namespace hearlov\custompanels\libs\muqsit\invmenu\type\graphic\network;

use hearlov\custompanels\libs\muqsit\invmenu\session\InvMenuInfo;
use hearlov\custompanels\libs\muqsit\invmenu\session\PlayerSession;
use hearlov\custompanels\libs\muqsit\invmenu\type\graphic\PositionedInvMenuGraphic;
use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

final class BlockInvMenuGraphicNetworkTranslator implements InvMenuGraphicNetworkTranslator{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void{
		$graphic = $current->graphic;
		if(!($graphic instanceof PositionedInvMenuGraphic)){
			throw new InvalidArgumentException("Expected " . PositionedInvMenuGraphic::class . ", got " . get_class($graphic));
		}

		$pos = $graphic->getPosition();
		$packet->blockPosition = new BlockPosition((int) $pos->x, (int) $pos->y, (int) $pos->z);
	}
}