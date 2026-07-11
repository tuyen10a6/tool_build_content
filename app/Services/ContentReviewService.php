<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\User;

class ContentReviewService
{
    public function __construct(
        private readonly ContentReviewNotificationService $contentReviewNotificationService,
    ) {
    }

    public function submitForReview(ContentItem $content, User $actor): void
    {
        $fromStatus = $content->approval_status;

        $content->update([
            'approval_status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        $this->logHistory($content, $actor, $fromStatus, 'pending_review', null);
        $this->contentReviewNotificationService->queueSubmittedForReview($content->fresh(['creator']));
    }

    public function updateReview(ContentItem $content, User $actor, string $toStatus, ?string $comment): void
    {
        $fromStatus = $content->approval_status;
        $attributes = [
            'approval_status' => $toStatus,
            'review_comment' => $comment,
            'reviewed_by' => $actor->id,
            'reviewed_by_name' => $actor->display_name,
            'reviewed_at' => now(),
        ];

        if ($toStatus === 'needs_revision') {
            $attributes['revision_requested_count'] = (int) $content->revision_requested_count + 1;
        }

        $content->update($attributes);

        $this->logHistory($content, $actor, $fromStatus, $toStatus, $comment);
        $this->contentReviewNotificationService->queueReviewOutcome($content->fresh(['creator']), $toStatus, $comment);
    }

    private function logHistory(ContentItem $content, User $actor, ?string $fromStatus, string $toStatus, ?string $comment): void
    {
        $content->reviewHistories()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'acted_by' => $actor->id,
            'acted_by_name' => $actor->display_name,
            'acted_role' => $actor->role,
        ]);
    }
}
