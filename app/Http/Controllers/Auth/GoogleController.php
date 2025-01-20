<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\GoogleDrive;
use App\Models\Workspace;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            // If user is already authenticated, redirect to dashboard
            if (session('user_email')) {
                Log::info('User already authenticated, redirecting to dashboard', [
                    'email' => session('user_email')
                ]);
                return redirect()->route('dashboard');
            }

            Log::info('Initiating Google OAuth redirect');
            
            return Socialite::driver('google')
                ->scopes([
                    'https://www.googleapis.com/auth/drive.readonly',
                    'https://www.googleapis.com/auth/drive.metadata.readonly'
                ])
                ->with([
                    'access_type' => 'offline',
                    'prompt' => 'consent select_account'
                ])
                ->redirect();
            
        } catch (\Exception $e) {
            Log::error('Google redirect failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('home')
                ->with('error', 'Failed to initiate Google sign-in');
        }
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            // If user is already authenticated, redirect to dashboard
            if (session('user_email')) {
                Log::info('User already authenticated in callback, redirecting to dashboard', [
                    'email' => session('user_email')
                ]);
                return redirect()->route('dashboard');
            }

            Log::info('Starting Google callback handling', [
                'has_code' => $request->has('code'),
                'has_state' => $request->has('state')
            ]);

            if (!$request->has('code')) {
                throw new Exception('No authorization code provided');
            }

            // Get the Socialite driver
            $driver = Socialite::driver('google');
            
            try {
                $user = $driver->user();
            } catch (\Exception $e) {
                Log::error('Socialite user retrieval failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Try stateless
                Log::info('Attempting stateless authentication');
                $user = $driver->stateless()->user();
            }

            if (!$user) {
                throw new Exception('Failed to retrieve user information from Google');
            }

            Log::info('Google user retrieved', [
                'email' => $user->email,
                'id' => $user->id,
                'has_refresh_token' => !empty($user->refreshToken),
                'expires_in' => $user->expiresIn ?? null,
                'token' => !empty($user->token)
            ]);
            
            if (empty($user->email)) {
                throw new Exception('No email provided by Google');
            }
            
            // Store the email in session
            session(['user_email' => $user->email]);
            
            // Get domain name and format it for workspace name
            $domain = Str::after($user->email, '@');
            $workspaceName = Str::title(Str::before($domain, '.')) . ' Workspace';

            // Create or retrieve workspace
            $workspace = Workspace::firstOrCreate(
                ['domain' => $domain],
                ['name' => $workspaceName]
            );
            Log::info('Workspace created/retrieved', [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'domain' => $workspace->domain
            ]);

            // Check if this Google Drive connection already exists
            $existingDrive = GoogleDrive::where('drive_id', $user->id)
                ->where('workspace_id', $workspace->id)
                ->first();

            if (!$existingDrive) {
                if (empty($user->refreshToken)) {
                    Log::error('No refresh token provided by Google', [
                        'email' => $user->email,
                        'id' => $user->id
                    ]);
                    
                    // Force consent screen
                    return redirect()->route('auth.google')
                        ->with('error', 'Please grant all required permissions');
                }

                // Store the Google Drive connection
                $googleDrive = GoogleDrive::create([
                    'workspace_id' => $workspace->id,
                    'drive_id' => $user->id,
                    'name' => $user->email,
                    'access_token' => $user->token,
                    'refresh_token' => $user->refreshToken,
                    'token_expires_at' => now()->addSeconds($user->expiresIn ?? 3600),
                ]);
                Log::info('New Google Drive connection stored', [
                    'id' => $googleDrive->id,
                    'email' => $user->email
                ]);
            } else {
                // Update existing connection
                $existingDrive->update([
                    'access_token' => $user->token,
                    'refresh_token' => $user->refreshToken ?? $existingDrive->refresh_token,
                    'token_expires_at' => now()->addSeconds($user->expiresIn ?? 3600),
                ]);
                Log::info('Existing Google Drive connection updated', [
                    'id' => $existingDrive->id,
                    'email' => $user->email
                ]);
            }

            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            Log::error('Google callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Clear session on error
            $request->session()->invalidate();
            
            return redirect()->route('home')
                ->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }
}
