<?php

declare(strict_types=1);

namespace hearlov\custompanels\libs\muqsit\invmenu\type\util\builder;

use hearlov\custompanels\libs\muqsit\invmenu\type\InvMenuType;

interface InvMenuTypeBuilder{

	public function build() : InvMenuType;
}