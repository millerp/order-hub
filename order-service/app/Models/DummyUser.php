<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class DummyUser extends Authenticatable
{
    public $id;
    public $role;
}
