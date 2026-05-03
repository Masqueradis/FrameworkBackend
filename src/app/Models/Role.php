<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;
}
