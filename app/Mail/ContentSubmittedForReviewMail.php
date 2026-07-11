<?php

namespace App\Mail;

use App\Models\ContentItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContentSubmittedForReviewMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContentItem $contentItem)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Có content mới cần duyệt: '.$this->contentItem->name.' - CTV: '.$this->creatorName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contents.submitted-for-review',
            with: [
                'contentItem' => $this->contentItem,
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
