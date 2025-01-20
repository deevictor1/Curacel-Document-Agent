<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DocumentController;
use App\Models\Workspace;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\FileActivityController;
use Illuminate\Support\Facades\Log;

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])
    ->name('auth.google');
    
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])
    ->name('auth.google.callback');

// API routes should be in their own group
Route::prefix('api')->middleware(['web'])->group(function () {
    Route::get('/workspace/members', [WorkspaceController::class, 'getMembers'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/workspace/all-documents', [WorkspaceController::class, 'getAllMemberDocuments'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
});

// Protected web routes
Route::middleware(['web'])->group(function () {
    Route::get('/dashboard', function () {
        $workspace = Workspace::with(['googleDrives'])->first();
        
        // Get the currently logged in user's email from the session
        $userEmail = session('user_email');
        
        Log::info('Session user email:', ['email' => $userEmail]); // Debug log
        
        // Find the specific Google Drive record for the logged-in user
        $currentUserDrive = $workspace->googleDrives
            ->where('name', $userEmail)
            ->first();
        
        Log::info('Current user drive:', [
            'email' => $userEmail,
            'drive' => $currentUserDrive ? [
                'id' => $currentUserDrive->id,
                'name' => $currentUserDrive->name
            ] : null
        ]);
        
        return Inertia::render('Dashboard/Index', [
            'workspace' => [
                'name' => $workspace->name,
                'domain' => $workspace->domain,
                'google_drives' => $workspace->googleDrives->map(function($drive) use ($userEmail) {
                    return [
                        'id' => $drive->id,
                        'name' => $drive->name,
                        'is_current_user' => $drive->name === $userEmail
                    ];
                }),
                'current_user' => $currentUserDrive ? [
                    'id' => $currentUserDrive->id,
                    'name' => $currentUserDrive->name
                ] : null
            ]
        ]);
    })->name('dashboard');
    
    Route::get('/api/workspace/members', [WorkspaceController::class, 'getMembers']);
    Route::get('/api/workspace/all-documents', [WorkspaceController::class, 'getAllMemberDocuments']);

    // API routes for document management
    Route::prefix('api')->group(function () {
        Route::get('/documents/search', [DocumentController::class, 'search'])
            ->name('documents.search');
    });
});

// Add this with your other routes
Route::get('/api/documents/search', [DocumentController::class, 'search'])
    ->name('documents.search');

// Public routes
Route::get('/login', function () {
    return redirect('/auth/google');
})->name('login');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::post('/api/documents/request-access', [DocumentController::class, 'requestAccess']);
    Route::post('/api/documents/handle-request/{requestId}', [DocumentController::class, 'handleAccessRequest']);
    Route::get('/api/documents/pending-requests', [DocumentController::class, 'getPendingRequests']);
    Route::get('/api/file-activities', [FileActivityController::class, 'getNotifications']);
    Route::post('/api/file-activities/{id}/mark-read', [FileActivityController::class, 'markAsRead']);
});

Route::post('/logout', function () {
    session()->forget('user_email');
    return redirect()->route('home');
})->name('logout');

// Add this temporary debug route
Route::get('/debug/session', function () {
    return response()->json([
        'session_exists' => session()->has('user_email'),
        'user_email' => session('user_email'),
        'all_session' => session()->all()
    ]);
});
