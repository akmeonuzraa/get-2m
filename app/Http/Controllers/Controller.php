<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AuthorizesOwnership;

abstract class Controller
{
    use AuthorizesOwnership;
}
