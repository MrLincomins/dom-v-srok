<?php

declare(strict_types=1);

namespace App\Domain\Requests\Enums;

enum EventType: string
{
    case Created = 'created';
    case Assigned = 'assigned';
    case StatusChanged = 'status_changed';
    case Redirected = 'redirected';
    case Returned = 'returned';
    case Confirmed = 'confirmed';
    case Comment = 'comment';
    case PhotoAdded = 'photo_added';
    case ParticipantJoined = 'participant_joined';
    case Notification = 'notification';
    case Reminder = 'reminder';
}
