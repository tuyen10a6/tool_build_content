<?php

namespace App\Mail;

use App\Models\ContentItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContentReviewOutcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContentItem $contentItem,
        public string $statusLabel,
        public ?string $reviewComment,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kết quả duyệt content: '.$this->contentItem->name.' - CTV: '.$this->creatorName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contents.review-outcome',
            with: [
                'contentItem' => $this->contentItem,
                'statusLabel' => $this->statusLabel,
                'reviewComment' => $this->reviewComment,
                'contentUrl' => route('contents.show', $this->contentItem),
            ],
        );
    }

    private function creatorName(): string
    {
        return $this->contentItem->creator?->full_name
            ?: $this->contentItem->created_by_name
            ?: 'Không rõ';
    }
}
