<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;

class SlackController extends Controller
{
    public function redirectToSlack()
    {
        return Socialite::driver('slack')
            ->scopes([
                'channels:history',
                'channels:read',
                'chat:write',
                'files:read',
                'groups:history',
                'groups:read',
                'im:history',
                'im:read',
                'users:read'
            ])
            ->redirect();
    }

    public function handleSlackCallback()
    {
        try {
            $slackUser = Socialite::driver('slack')->user();

            // Store workspace information
            $workspace = Workspace::updateOrCreate(
                ['slack_workspace_id' => $slackUser->team['id']],
                [
                    'name' => $slackUser->team['name'],
                    'domain' => $slackUser->team['domain'],
                ]
            );

            return redirect()->route('dashboard')
                ->with('success', 'Slack workspace connected successfully!');

        } catch (Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
}
