<?php

namespace App\Services\Combustible;

/** Sólo se lanza si falló la adquisición, antes del callback de escrituras. */
class LockUnavailable extends \RuntimeException {}
