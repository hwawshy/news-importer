<?php

declare(strict_types=1);

namespace App\Enumeration;

enum ImportStatusEnumeration: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case RUNNING = 'running';
}
