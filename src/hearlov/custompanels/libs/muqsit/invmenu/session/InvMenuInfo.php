<?php

declare(strict_types=1);

namespace hearlov\custompanels\libs\muqsit\invmenu\session;

use hearlov\custompanels\libs\muqsit\invmenu\InvMenu;
use hearlov\custompanels\libs\muqsit\invmenu\type\graphic\InvMenuGraphic;

final class InvMenuInfo{

	public function __construct(
		readonly public InvMenu $menu,
		readonly public InvMenuGraphic $graphic,
		readonly public ?string $graphic_name
	){}
}