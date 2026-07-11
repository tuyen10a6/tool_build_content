<?php

namespace App\Services;

use App\Mail\ContentReviewOutcomeMail;
use App\Mail\ContentSubmittedForReviewMail;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ContentReviewNotificationService
{
    public function queueSubmittedForReview(ContentItem $content): void
    {
        User::query()
            ->whereIn('role', ['admin', 'reviewer'])
            ->where('status', 'active')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->get()
            ->each(function (User $recipient) use ($content): void {
                Mail::to($recipient->email)->queue(new ContentSubmittedForReviewMail($content));
            });
    }

    public function queueReviewOutcome(ContentItem $content, string $toStatus, ?string $comment): void
    {
        $owner = $content->creator;

        if (! $owner || $owner->status !== 'active' || ! $owner->email) {
            return;
        }

        if (! in_array($toStatus, ['needs_revision', 'approved'], true)) {
            return;
        }

        Mail::to($owner->email)->queue(new ContentReviewOutcomeMail(
            $content,
            $this->statusLabel($toStatus),
            $comment,
        ));
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'needs_revision' => 'Cần sửa',
            'approved' => 'Đã duyệt',
            'pending_review' => 'Chờ duyệt',
            'completed' => 'Hoàn thành',
            default => 'Mới',
        };
    }
}
