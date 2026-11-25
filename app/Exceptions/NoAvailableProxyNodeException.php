<?php

declare(strict_types=1);

namespace App\Exceptions;

final class NoAvailableProxyNodeException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('No enabled and routable proxy node is available.');
    }
}
