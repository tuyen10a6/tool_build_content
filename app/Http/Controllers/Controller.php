<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    protected function authorizeAdmin(): void
    {
        abort_unless($this->user()->isAdmin(), Response::HTTP_FORBIDDEN);
    }

    protected function authorizeOwnership(Model $model, string $column = 'created_by'): void
    {
        if ($this->user()->isAdmin()) {
            return;
        }

        abort_unless((int) $model->getAttribute($column) === (int) $this->user()->id, Response::HTTP_FORBIDDEN);
    }

    protected function authorizeContentView(ContentItem $content): void
    {
        if ($this->user()->canReviewContent()) {
            return;
        }

        abort_unless((int) $content->created_by === (int) $this->user()->id, Response::HTTP_FORBIDDEN);
    }

    protected function authorizeContentEdit(ContentItem $content): void
    {
        if ($this->user()->isAdmin()) {
            return;
        }

        abort_unless(
            $this->user()->role === 'user'
            && (int) $content->created_by === (int) $this->user()->id
            && $content->isEditableByOwner(),
            Response::HTTP_FORBIDDEN
        );
    }

    protected function authorizeContentReview(ContentItem $content): void
    {
        if ($this->user()->isAdmin()) {
            return;
        }

        abort_unless($this->user()->isReviewer(), Response::HTTP_FORBIDDEN);
    }

    protected function authorizeContentExport(ContentItem $content): void
    {
        $this->authorizeAdmin();
    }

    protected function authorizeSceneEdit(Scene $scene): void
    {
        if ($this->user()->isAdmin()) {
            return;
        }

        abort_unless(
            $this->user()->role === 'user'
            && (int) $scene->created_by === (int) $this->user()->id
            && $scene->content
            && $scene->content->isEditableByOwner(),
            Response::HTTP_FORBIDDEN
        );
    }
}
