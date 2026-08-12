<?php

declare(strict_types=1);

namespace Modules\Example\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ExampleController extends Controller
{
    public function index(): View
    {
        return view('example::index');
    }

    public function about(): View
    {
        return view('example::about');
    }
}
