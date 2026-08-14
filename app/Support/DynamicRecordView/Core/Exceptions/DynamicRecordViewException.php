<?php

namespace App\Support\DynamicRecordView\Core\Exceptions;

use RuntimeException;

/**
 * Base class for every Dynamic Record View configuration exception. Thrown
 * only for developer mistakes (duplicate keys, unknown references, invalid
 * definitions) — never for authorization or "record not found", which are
 * handled by RecordResolver via plain 404s.
 */
abstract class DynamicRecordViewException extends RuntimeException {}
