<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use App\Models\ForumPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContentReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = ContentReport::with('reportedBy')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'total_pages' => $reports->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'content_type' => ['required', 'string', 'in:forum_topic,forum_post,blog_article,blog_comment'],
            'content_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'in:spam,inappropriate,offensive,other'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['reported_by'] = $request->user()->id;

        $report = ContentReport::create($data);

        // Update report count on the content
        if ($data['content_type'] === 'forum_post') {
            ForumPost::where('id', $data['content_id'])->update([
                'report_count' => ContentReport::where('content_type', 'forum_post')
                    ->where('content_id', $data['content_id'])
                    ->count(),
                'last_report_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'data' => $report], 201);
    }

    public function resolve(int $id): JsonResponse
    {
        $report = ContentReport::findOrFail($id);
        $report->update([
            'status' => 'reviewed',
            'resolved_by' => request()->user()->id,
            'resolved_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function dismiss(int $id): JsonResponse
    {
        $report = ContentReport::findOrFail($id);
        $report->update([
            'status' => 'dismissed',
            'resolved_by' => request()->user()->id,
            'resolved_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
