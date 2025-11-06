<?php

namespace App\Listeners;

use App\Events\ImportErrorOccurred;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class SendImportErrorNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ImportErrorOccurred $event): void
    {
        try {
            // Get user who initiated the import
            $user = User::find($event->userId);
            
            if (!$user || !$user->email) {
                Log::warning("Cannot send import error notification - user not found or no email: {$event->userId}");
                return;
            }

            // Send email notification
            Mail::raw(
                $this->buildEmailContent($event),
                function ($message) use ($user, $event) {
                    $message->to($user->email, $user->name)
                           ->subject('Import Error Notification - Import ID: ' . $event->importRecord->id);
                }
            );

            Log::info("Import error notification sent to user: {$user->email}");

        } catch (\Exception $e) {
            Log::error("Failed to send import error notification: " . $e->getMessage());
        }
    }

    /**
     * Build email content
     */
    private function buildEmailContent($event)
    {
        $userName = $event->importRecord->user->name ?? 'User';
        $importId = $event->importRecord->id;
        $importType = $event->importRecord->import_type;
        $createdAt = $event->importRecord->created_at;
        $errorMessage = $event->errorMessage;

        return "
Dear {$userName},

An error occurred during your data import process.

Import Details:
- Import ID: {$importId}
- Import Type: {$importType}
- Started At: {$createdAt}
- Status: Failed

Error Message:
{$errorMessage}

Please review your import file and try again. If the problem persists, contact the system administrator.

Best regards,
Mass Data Importer System
        ";
    }
}
