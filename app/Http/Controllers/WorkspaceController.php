<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\GoogleDrive;
use Google_Client;
use Google_Service_Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkspaceController extends Controller
{
    public function getMembers(Request $request)
    {
        try {
            $userEmail = session('user_email');
            Log::info('Starting to fetch workspace members', ['user_email' => $userEmail]);

            $workspace = Workspace::first();
            if (!$workspace) {
                return response()->json([]);
            }

            // Get all drives/members for this workspace
            $drives = GoogleDrive::where('workspace_id', $workspace->id)->get();
            
            Log::info('Found drives', [
                'count' => $drives->count(),
                'drives' => $drives->pluck('name')->toArray()
            ]);

            $members = [];
            foreach ($drives as $drive) {
                try {
                    Log::info('Processing drive', ['email' => $drive->name]);

                    $client = new Google_Client();
                    $client->setClientId(config('services.google.client_id'));
                    $client->setClientSecret(config('services.google.client_secret'));
                    $client->setAccessToken([
                        'access_token' => $drive->access_token,
                        'refresh_token' => $drive->refresh_token,
                        'expires_in' => $drive->token_expires_at->diffInSeconds(now()),
                    ]);

                    if ($client->isAccessTokenExpired()) {
                        Log::info('Token expired, refreshing', ['email' => $drive->name]);
                        $client->fetchAccessTokenWithRefreshToken($drive->refresh_token);
                        $tokens = $client->getAccessToken();
                        
                        $drive->update([
                            'access_token' => $tokens['access_token'],
                            'token_expires_at' => now()->addSeconds($tokens['expires_in']),
                        ]);
                    }

                    $service = new Google_Service_Drive($client);

                    // Get document count for this member with pagination
                    $query = "'" . $drive->name . "' in owners and mimeType != 'application/vnd.google-apps.folder' and trashed = false";
                    $pageToken = null;
                    $documentCount = 0;

                    do {
                        $optParams = [
                            'q' => $query,
                            'fields' => 'nextPageToken, files(id)',
                            'pageSize' => 1000,
                            'pageToken' => $pageToken
                        ];

                        $response = $service->files->listFiles($optParams);
                        $documentCount += count($response->getFiles());
                        $pageToken = $response->getNextPageToken();

                        Log::info('Fetched page of documents', [
                            'email' => $drive->name,
                            'count_so_far' => $documentCount,
                            'has_more' => !empty($pageToken)
                        ]);

                    } while ($pageToken != null);

                    // Extract name from email
                    $name = explode('@', $drive->name)[0];
                    $name = ucwords(str_replace(['.', '_'], ' ', $name));

                    Log::info('Final document count', [
                        'email' => $drive->name,
                        'count' => $documentCount
                    ]);

                    $members[] = [
                        'email' => $drive->name,
                        'name' => $name,
                        'documentCount' => $documentCount
                    ];

                } catch (\Exception $e) {
                    Log::error('Error processing member', [
                        'drive' => $drive->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Still include the member, but with 0 documents
                    $name = explode('@', $drive->name)[0];
                    $name = ucwords(str_replace(['.', '_'], ' ', $name));
                    
                    $members[] = [
                        'email' => $drive->name,
                        'name' => $name,
                        'documentCount' => 0
                    ];
                    continue;
                }
            }

            // Sort members by name
            usort($members, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            Log::info('Successfully processed all members', [
                'count' => count($members),
                'members' => $members
            ]);

            return response()->json($members)
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            Log::error('Failed to get workspace members', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to load workspace members: ' . $e->getMessage()
            ], 500);
        }
    }

    // Add a new endpoint for searching documents across all members
    public function getAllMemberDocuments(Request $request)
    {
        try {
            $workspace = Workspace::first();
            $query = $request->input('q', ''); // Search query if provided
            
            $results = [];
            foreach ($workspace->googleDrives as $drive) {
                // ... existing document search logic ...
                // but without filtering by specific member
            }

            return response()->json([
                'documents' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get all member documents: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load documents'
            ], 500);
        }
    }
} 