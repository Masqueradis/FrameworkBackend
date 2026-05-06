<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class EmptyCartException extends Exception
{
    protected $message = 'Cannot proceed to checkout with an empty cart.';
}
