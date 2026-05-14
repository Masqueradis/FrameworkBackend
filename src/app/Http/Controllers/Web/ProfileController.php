<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\ApiController;
use Illuminate\View\View;

class ProfileController extends ApiController
{
    public function index(): View
    {
        return view('dashboard');
    }
}
