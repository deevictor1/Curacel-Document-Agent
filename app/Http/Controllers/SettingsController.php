<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Workspace;
use App\Models\GoogleDrive;

class SettingsController extends Controller
{
    public function index()
    {
        $workspace = Workspace::where('user_id', auth()->id())
            ->with('googleDrives')
            ->first();

        return Inertia::render('Settings/Index', [
            'workspace' => [
                'slack_connected' => !is_null($workspace),
                'google_connected' => $workspace?->googleDrives()->exists() ?? false,
            ]
        ]);
    }
} 