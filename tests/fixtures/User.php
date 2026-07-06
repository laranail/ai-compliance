<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as AuthUser;

class User extends AuthUser
{
    protected $table = 'users';

    protected $guarded = [];
}
