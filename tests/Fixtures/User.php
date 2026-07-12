<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as AuthUser;

class User extends AuthUser implements FilamentUser
{
    protected $table = 'users';

    protected $guarded = [];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
