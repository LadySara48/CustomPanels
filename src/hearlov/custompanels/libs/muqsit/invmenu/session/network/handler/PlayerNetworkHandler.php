<?php

declare(strict_types=1);

namespace hearlov\custompanels\libs\muqsit\invmenu\session\network\handler;

use Closure;
use hearlov\custompanels\libs\muqsit\invmenu\session\network\NetworkStackLatencyEntry;

interface PlayerNetworkHandler{

	public function createNetworkStackLatencyEntry(Closure $then) : NetworkStackLatencyEntry;
}