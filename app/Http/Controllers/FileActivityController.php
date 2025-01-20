<?php

namespace App\Http\Controllers;

use App\Models\FileActivity;
use App\Models\GoogleDrive;
use Google_Client;
use Google_Service_Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileActivityController extends Controller
{
    public function trackChanges()
    {
        try {
            $workspace = \App\Models\Workspace::first();
            $drives = GoogleDrive::where('workspace_id', $workspace->id)->get();

            foreach ($drives as $drive) {
                $client = new Google_Client();
                // ... set up client credentials ...

                $service = new Google_Service_Drive($client);

                // Get changes using Drive API's changes.list endpoint
                $startPageToken = $service->changes->getStartPageToken()->getStartPageToken();
                $pageToken = $startPageToken;

                do {
                    $changes = $service->changes->listChanges($pageToken, [
                        'spaces' => 'drive',
                        'includeItemsFromAllDrives' => true,
                        'supportsAllDrives' => true,
                        'fields' => 'nextPageToken, newStartPageToken, changes(file(id, name, owners, modifiedTime))'
                    ]);

                    foreach ($changes->getChanges() as $change) {
                        $file = $change->getFile();
                        if ($file) {
                            $owners = $file->getOwners();
                            $creator = $owners ? $owners[0]->getEmailAddress() : null;

                            if ($creator) {
                                FileActivity::create([
                                    'file_id' => $file->getId(),
                                    'file_name' => $file->getName(),
                                    'actor_email' => $drive->name,
                                    'creator_email' => $creator,
                                    'action_type' => $this->determineActionType($change),
                                    'changes' => [
                                        'modified_time' => $file->getModifiedTime(),
                                        'details' => 'File was modified'
                                    ]
                                ]);
                            }
                        }
                    }

                    $pageToken = $changes->getNextPageToken();
                } while ($pageToken != null);

            }

            return response()->json(['message' => 'Changes tracked successfully']);

        } catch (\Exception $e) {
            Log::error('Failed to track changes: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to track changes'], 500);
        }
    }

    private function determineActionType($change)
    {
        // Logic to determine the type of change
        if (!$change->getFile()) {
            return 'delete';
        }
        // You can expand this based on the change metadata
        return 'edit';
    }

    public function getNotifications(Request $request)
    {
        try {
            $activities = FileActivity::where('creator_email', $request->user_email)
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($activities);
        } catch (\Exception $e) {
            Log::error('Failed to get notifications: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch notifications'], 500);
        }
    }

    public function markAsRead(Request $request, $activityId)
    {
        try {
            FileActivity::where('id', $activityId)->update(['is_read' => true]);
            return response()->json(['message' => 'Marked as read']);
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update notification'], 500);
        }
    }
} 