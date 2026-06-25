<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductAttribute: string
{
    case Memory = 'memory';
    case Color = 'color';
    case Storage = 'storage';
    case Material = 'material';
    case RAM = 'ram';
    case Volume = 'volume';
}
