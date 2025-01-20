<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $workspace = Workspace::with('google_drives')->first();
        
        return Inertia::render('Dashboard/Index', [
            'title' => 'Dashboard',
            'workspace' => $workspace
        ]);
    }
} 