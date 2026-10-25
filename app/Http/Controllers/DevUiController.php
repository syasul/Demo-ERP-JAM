<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class DevUiController extends Controller
{
    public function index(): View
    {
        abort_unless(app()->environment('local'), 404);

        return view('dev.ui');
    }
}
