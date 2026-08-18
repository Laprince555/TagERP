<?php

namespace Modules\Inventory\Exceptions;

use Exception;
use Illuminate\Http\Response;

class InsufficientStockException extends Exception
{
    public function __construct($message = 'Insufficient stock available', $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        return response()->json([
            'message' => $this->message,
            'error_code' => 'INSUFFICIENT_STOCK',
            'status' => 'error',
        ], Response::HTTP_BAD_REQUEST);
    }
}
