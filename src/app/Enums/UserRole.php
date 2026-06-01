<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole:string
{
    case Admin = 'admin';
    case Seller = 'seller';
    case Customer = 'customer';
    case Manager = 'manager';
}
