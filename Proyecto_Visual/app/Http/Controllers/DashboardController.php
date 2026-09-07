<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Muestra el panel de control principal de la aplicación.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'user' => $request->user(),
        ]);
    }
}
