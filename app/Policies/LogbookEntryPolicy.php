<?php

namespace App\Policies;

use App\Models\LogbookEntry;
use App\Models\User;

class LogbookEntryPolicy
{
    /**
     * Determine whether the user can view a specific logbook entry.
     */
    public function view(User $user, LogbookEntry $entry): bool
    {
        $isOwner = $user->id === $entry->user_id;
        $isSupervisor = $user->supervisesLogbookEntry($entry);
        $isAdmin = $user->isAdmin();

        return $isOwner || $isSupervisor || $isAdmin;
    }

    /**
     * Determine whether the user can update the entry as a student.
     * (Faculty review is handled in review().)
     */
    public function update(User $user, LogbookEntry $entry): bool
    {
        $isOwner = $user->id === $entry->user_id;

        if (! $isOwner) {
            return false;
        }

        // Students can only edit drafts or just-submitted entries.
        return in_array($entry->status, ['draft', 'submitted'], true);
    }

    /**
     * Determine whether the user can perform review actions
     * (approve / request revision) as a faculty supervisor.
     */
    public function review(User $user, LogbookEntry $entry): bool
    {
        $isSupervisor = $user->supervisesLogbookEntry($entry);
        $isAdmin = $user->isAdmin();

        if (! ($isSupervisor || $isAdmin)) {
            return false;
        }

        // Only review entries that are in the faculty workflow.
        return in_array($entry->status, ['pending_review', 'submitted'], true);
    }
}
