<?php

namespace App\Support\DynamicTable\Core\Exceptions;

use RuntimeException;

/**
 * Base exception for all Dynamic Table Engine configuration errors.
 * Catch this broadly to handle any config-time failure from the engine.
 */
abstract class DynamicTableException extends RuntimeException {}
