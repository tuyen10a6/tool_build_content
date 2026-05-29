<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $selectedUserIds = collect($request->input('user_ids', []))
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();

        $fromDate = $request->string('from_date')->toString();
        $toDate = $request->string('to_date')->toString();
        $detailUserId = $request->integer('detail_user_id');

        $users = User::query()->orderBy('full_name')->orderBy('username')->get();

        $contentCounts = ContentItem::query()
            ->selectRaw('created_by, COUNT(*) as aggregate')
            ->when($selectedUserIds !== [], fn ($query) => $query->whereIn('created_by', $selectedUserIds))
            ->when($fromDate, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
            ->groupBy('created_by')
            ->pluck('aggregate', 'created_by');

        $sceneCounts = Scene::query()
            ->selectRaw('created_by, COUNT(*) as aggregate')
            ->where('scene_type', 'main')
            ->when($selectedUserIds !== [], fn ($query) => $query->whereIn('created_by', $selectedUserIds))
            ->when($fromDate, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
            ->groupBy('created_by')
            ->pluck('aggregate', 'created_by');

        $reportRows = $users
            ->when($selectedUserIds !== [], fn ($collection) => $collection->whereIn('id', $selectedUserIds))
            ->map(function (User $user) use ($contentCounts, $sceneCounts) {
                return [
                    'user' => $user,
                    'content_count' => (int) ($contentCounts[$user->id] ?? 0),
                    'scene_count' => (int) ($sceneCounts[$user->id] ?? 0),
                ];
            })
            ->values();

        $detailUser = $detailUserId ? $users->firstWhere('id', $detailUserId) : null;

        $contentDetails = collect();
        $sceneDetails = collect();

        if ($detailUser) {
            $contentDetails = ContentItem::query()
                ->where('created_by', $detailUser->id)
                ->when($fromDate, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
                ->when($toDate, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
                ->orderByDesc('created_at')
                ->get();

            $sceneDetails = Scene::query()
                ->with('content')
                ->where('scene_type', 'main')
                ->where('created_by', $detailUser->id)
                ->when($fromDate, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
                ->when($toDate, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
                ->orderByDesc('created_at')
                ->get();
        }

        return view('reports.index', [
            'users' => $users,
            'reportRows' => $reportRows,
            'selectedUserIds' => $selectedUserIds,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'detailUser' => $detailUser,
            'contentDetails' => $contentDetails,
            'sceneDetails' => $sceneDetails,
        ]);
    }
}
