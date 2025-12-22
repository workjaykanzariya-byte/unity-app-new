<?php

namespace App\Listeners;

use App\Events\ActivityCreated;
use App\Mail\ActivityActorMail;
use App\Mail\ActivityAdminMail;
use App\Mail\ActivityOtherUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendActivityEmails
{
    public function handle(ActivityCreated $event): void
    {
        if (! config('activity_emails.enabled')) {
            return;
        }

        try {
            $actor = User::find($event->actorUserId);
            $otherUser = $event->otherUserId ? User::find($event->otherUserId) : null;
            $activityAttributes = $event->activityModel->getAttributes();
            $activityTitle = $this->deriveActivityTitle($event->activityModel, $event->activityType);

            $this->sendActorEmail($event, $actor, $otherUser, $activityAttributes, $activityTitle);
            $this->sendOtherUserEmail($event, $actor, $otherUser, $activityAttributes, $activityTitle);
            $this->sendAdminEmail($event, $actor, $activityAttributes, $activityTitle);
        } catch (Throwable $e) {
            Log::error('Failed to dispatch activity emails', [
                'activity_type' => $event->activityType,
                'activity_id' => $event->activityModel->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendActorEmail(
        ActivityCreated $event,
        ?User $actor,
        ?User $otherUser,
        array $activityAttributes,
        string $activityTitle,
    ): void {
        if (! config('activity_emails.send_actor_copy')) {
            return;
        }

        if (! $actor || empty($actor->email)) {
            return;
        }

        try {
            Mail::to($actor->email)->send(new ActivityActorMail(
                $event->activityType,
                $activityTitle,
                $actor,
                $otherUser,
                $activityAttributes,
            ));
        } catch (Throwable $e) {
            Log::error('Failed to send actor activity email', [
                'activity_type' => $event->activityType,
                'activity_id' => $event->activityModel->getKey(),
                'actor_id' => $actor->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendOtherUserEmail(
        ActivityCreated $event,
        ?User $actor,
        ?User $otherUser,
        array $activityAttributes,
        string $activityTitle,
    ): void {
        if (! config('activity_emails.send_other_user')) {
            return;
        }

        if (! $otherUser || empty($otherUser->email)) {
            return;
        }

        if ($event->otherUserId === null) {
            return;
        }

        try {
            Mail::to($otherUser->email)->send(new ActivityOtherUserMail(
                $event->activityType,
                $activityTitle,
                $actor,
                $otherUser,
                $activityAttributes,
            ));
        } catch (Throwable $e) {
            Log::error('Failed to send other user activity email', [
                'activity_type' => $event->activityType,
                'activity_id' => $event->activityModel->getKey(),
                'other_user_id' => $otherUser->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendAdminEmail(
        ActivityCreated $event,
        ?User $actor,
        array $activityAttributes,
        string $activityTitle,
    ): void {
        if (strtolower($event->activityType) !== 'requirement') {
            return;
        }

        $adminInbox = config('activity_emails.admin_inbox');

        if (empty($adminInbox)) {
            return;
        }

        try {
            Mail::to($adminInbox)->send(new ActivityAdminMail(
                $event->activityType,
                $activityTitle,
                $actor,
                $activityAttributes,
            ));
        } catch (Throwable $e) {
            Log::error('Failed to send admin activity email', [
                'activity_type' => $event->activityType,
                'activity_id' => $event->activityModel->getKey(),
                'admin_inbox' => $adminInbox,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function deriveActivityTitle(object $model, string $activityType): string
    {
        $potentialKeys = ['title', 'subject', 'content', 'comment', 'description'];

        foreach ($potentialKeys as $key) {
            if (! empty($model->{$key})) {
                return (string) $model->{$key};
            }
        }

        return $activityType;
    }
}
