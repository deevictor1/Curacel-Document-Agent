<?php

namespace App\Http\Controllers;

use App\Models\GoogleDrive;
use App\Models\Workspace;
use App\Models\AccessRequest;
use Google_Client;
use Google_Service_Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    private function getDocumentType($mimeType)
    {
        $typeMap = [
            'application/vnd.google-apps.document' => 'document',
            'application/vnd.google-apps.spreadsheet' => 'spreadsheet',
            'application/vnd.google-apps.presentation' => 'presentation',
            'application/pdf' => 'pdf',
            'text/csv' => 'csv',
            'application/vnd.google-apps.folder' => 'folder',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'spreadsheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'presentation',
            'application/vnd.ms-excel' => 'spreadsheet',
            'application/vnd.ms-powerpoint' => 'presentation',
            'application/msword' => 'document',
        ];

        return $typeMap[$mimeType] ?? 'unknown';
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $ownerEmail = $request->input('owner');
        $pageToken = $request->input('pageToken');

        try {
            $workspace = Workspace::first();
            if (!$workspace) {
                return response()->json([]);
            }

            $drives = $ownerEmail 
                ? GoogleDrive::where('workspace_id', $workspace->id)
                    ->where('name', $ownerEmail)
                    ->get()
                : GoogleDrive::where('workspace_id', $workspace->id)->get();

            $results = [];
            $nextPageToken = null;
            
            foreach ($drives as $drive) {
                $client = new Google_Client();
                $client->setClientId(config('services.google.client_id'));
                $client->setClientSecret(config('services.google.client_secret'));
                $client->setAccessToken([
                    'access_token' => $drive->access_token,
                    'refresh_token' => $drive->refresh_token,
                    'expires_in' => $drive->token_expires_at->diffInSeconds(now()),
                ]);

                if ($client->isAccessTokenExpired()) {
                    $client->fetchAccessTokenWithRefreshToken($drive->refresh_token);
                    $drive->update([
                        'access_token' => $client->getAccessToken()['access_token'],
                        'token_expires_at' => now()->addSeconds($client->getAccessToken()['expires_in']),
                    ]);
                }

                $service = new Google_Service_Drive($client);

                try {
                    $optParams = [
                        'pageSize' => 100, // Fixed at 100 items per request
                        'fields' => 'nextPageToken, files(id, name, mimeType, webViewLink, createdTime, modifiedTime, owners)',
                        'q' => $query ? "name contains '$query'" : "",
                        'orderBy' => 'modifiedTime desc',
                        'pageToken' => $pageToken
                    ];

                    $response = $service->files->listFiles($optParams);
                    
                    foreach ($response->getFiles() as $file) {
                        try {
                            $creator = $file->getOwners()[0];
                            $currentUserEmail = session('user_email');
                            
                            // Get the current user's permission for this file
                            $permissions = $service->permissions->listPermissions(
                                $file->getId(),
                                ['fields' => 'permissions(role,emailAddress,domain,type)']
                            );

                            Log::info('Checking permissions for file:', [
                                'file_name' => $file->getName(),
                                'current_user' => $currentUserEmail,
                                'permissions' => $permissions
                            ]);
                            
                            // Check if user has access through any means
                            $hasAccess = false;
                            foreach ($permissions as $perm) {
                                // Direct user permission
                                if ($perm->emailAddress === $currentUserEmail) {
                                    Log::info('Direct access found for user', [
                                        'file' => $file->getName(),
                                        'user' => $currentUserEmail
                                    ]);
                                    $hasAccess = true;
                                    break;
                                }
                                
                                // Domain-wide permission
                                if ($perm->type === 'domain' && 
                                    strpos($currentUserEmail, $perm->domain) !== false) {
                                    Log::info('Domain-wide access found', [
                                        'file' => $file->getName(),
                                        'domain' => $perm->domain
                                    ]);
                                    $hasAccess = true;
                                    break;
                                }
                                
                                // Anyone with link permission
                                if ($perm->type === 'anyone') {
                                    Log::info('Public access found', [
                                        'file' => $file->getName()
                                    ]);
                                    $hasAccess = true;
                                    break;
                                }
                            }

                            $results[] = [
                                'id' => $file->getId(),
                                'name' => $file->getName(),
                                'type' => $this->getDocumentType($file->getMimeType()),
                                'webViewLink' => $file->getWebViewLink(),
                                'creator' => [
                                    'name' => $creator->getDisplayName(),
                                    'email' => $creator->getEmailAddress()
                                ],
                                'modifiedTime' => $file->getModifiedTime(),
                                'hasAccess' => $hasAccess,
                                'isOwner' => $creator->getEmailAddress() === $currentUserEmail,
                                'accessType' => $hasAccess ? 'granted' : 'none'
                            ];
                        } catch (\Exception $e) {
                            Log::error('Error checking file permissions: ' . $e->getMessage(), [
                                'file' => $file->getName(),
                                'user' => $currentUserEmail
                            ]);
                            continue;
                        }
                    }

                    // Store the next page token
                    $nextPageToken = $response->getNextPageToken();
                    
                } catch (\Exception $e) {
                    Log::error('Drive search failed', [
                        'drive' => $drive->name,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            return response()->json([
                'files' => $results,
                'hasMore' => !empty($nextPageToken),
                'nextPageToken' => $nextPageToken
            ]);

        } catch (\Exception $e) {
            Log::error('Search failed: ' . $e->getMessage());
            return response()->json(['error' => 'Search failed'], 500);
        }
    }

    public function requestAccess(Request $request)
    {
        try {
            $accessRequest = AccessRequest::create([
                'file_id' => $request->file_id,
                'file_name' => $request->file_name,
                'requester_email' => $request->requester_email,
                'owner_email' => $request->owner_email,
                'status' => 'pending'
            ]);

            return response()->json([
                'message' => 'Access request sent successfully',
                'request_id' => $accessRequest->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create access request: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send access request'], 500);
        }
    }

    public function handleAccessRequest(Request $request, $requestId)
    {
        try {
            $accessRequest = AccessRequest::findOrFail($requestId);
            
            if ($request->action === 'approve') {
                // Get the owner's drive connection
                $ownerDrive = GoogleDrive::where('name', $accessRequest->owner_email)->first();
                
                $client = new Google_Client();
                // ... set up client credentials ...

                $service = new Google_Service_Drive($client);

                // Create the permission
                $newPermission = new \Google_Service_Drive_Permission([
                    'type' => 'user',
                    'role' => 'reader',
                    'emailAddress' => $accessRequest->requester_email
                ]);

                $service->permissions->create($accessRequest->file_id, $newPermission, [
                    'sendNotificationEmail' => false
                ]);

                $accessRequest->update(['status' => 'approved']);
            } else {
                $accessRequest->update(['status' => 'rejected']);
            }

            return response()->json(['message' => 'Access request handled successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to handle access request: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to handle access request'], 500);
        }
    }

    public function getPendingRequests(Request $request)
    {
        try {
            $requests = AccessRequest::where('owner_email', $request->user_email)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Failed to get pending requests: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch pending requests'], 500);
        }
    }
}
