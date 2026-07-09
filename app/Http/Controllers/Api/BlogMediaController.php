<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogMedium;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class BlogMediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $media = BlogMedium::where('store_id', $store->id)
            ->when($request->query('search'), fn ($q, $v) => $q->where('file_name', 'like', "%{$v}%"))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('per_page', 30), 100));

        return response()->json([
            'success' => true,
            'data' => $media->items(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
                'total_pages' => $media->lastPage(),
            ],
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $path = $file->store("blog/{$store->id}", 'public');

        $media = BlogMedium::create([
            'store_id' => $store->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'alt_text' => $request->input('alt_text'),
            'disk' => 'public',
        ]);

        return response()->json(['success' => true, 'data' => $media], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = Store::where('owner_id', $request->user()->id)->firstOrFail();
        $media = BlogMedium::where('store_id', $store->id)->findOrFail($id);
        Storage::disk($media->disk)->delete($media->file_path);
        $media->delete();

        return response()->json(['success' => true]);
    }
}
