<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestPint extends Model
{
    public function testMethod()
    {
        $a = 1;
        $b = 22;

        return $a + $b;
    }
}
