<?php

namespace Gravity_Forms\Gravity_SMTP\Routing;

abstract class Conditional {

	abstract public function resolve(): bool;

}
