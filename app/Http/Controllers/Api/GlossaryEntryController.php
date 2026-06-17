<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlossaryEntry;
use App\Models\PendingGlossaryTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class GlossaryEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GlossaryEntry::query()->orderBy('key');

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 100), 200);

        return response()->json($query->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(GlossaryEntry::findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:50', 'unique:glossary_entries,key'],
            'description' => ['required', 'string', 'max:255'],
            'search_patterns' => ['required', 'array', 'min:1'],
            'search_patterns.*' => ['required', 'string', 'max:100'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'account_reference' => ['nullable', 'string', 'max:100'],
            'is_income' => ['boolean'],
            'suggested_supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        ]);

        $data['status'] = 'approved';

        $entry = GlossaryEntry::create($data);

        return response()->json($entry, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $entry = GlossaryEntry::findOrFail($id);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:50', 'unique:glossary_entries,key,'.$id],
            'description' => ['required', 'string', 'max:255'],
            'search_patterns' => ['required', 'array', 'min:1'],
            'search_patterns.*' => ['required', 'string', 'max:100'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'account_reference' => ['nullable', 'string', 'max:100'],
            'is_income' => ['boolean'],
            'status' => ['nullable', 'string', 'in:approved,pending'],
            'suggested_supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        ]);

        $entry->update($data);

        return response()->json($entry);
    }

    public function destroy(int $id): JsonResponse
    {
        GlossaryEntry::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    // ─── Pending Terms ─────────────────────────────────────────────────────

    public function pendingTerms(Request $request): JsonResponse
    {
        $query = PendingGlossaryTerm::query()->where('status', 'pending')->orderByDesc('created_at');

        return response()->json($query->paginate(50));
    }

    public function approvePending(Request $request, int $id): JsonResponse
    {
        $pending = PendingGlossaryTerm::findOrFail($id);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:50', 'unique:glossary_entries,key'],
            'description' => ['required', 'string', 'max:255'],
            'search_patterns' => ['nullable', 'array'],
            'search_patterns.*' => ['string', 'max:100'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'account_reference' => ['nullable', 'string', 'max:100'],
            'is_income' => ['boolean'],
            'suggested_supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        ]);

        GlossaryEntry::create([
            'key' => $data['key'],
            'description' => $data['description'],
            'search_patterns' => $data['search_patterns'] ?? [$pending->term],
            'default_amount' => $data['default_amount'] ?? null,
            'account_reference' => $data['account_reference'] ?? null,
            'is_income' => $data['is_income'] ?? false,
            'status' => 'approved',
            'source' => 'auto_feed',
            'suggested_supplier_id' => $data['suggested_supplier_id'] ?? null,
        ]);

        $pending->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function dismissPending(int $id): JsonResponse
    {
        $pending = PendingGlossaryTerm::findOrFail($id);
        $pending->update([
            'status' => 'dismissed',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function dismissAllPending(): JsonResponse
    {
        PendingGlossaryTerm::where('status', 'pending')->update([
            'status' => 'dismissed',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return response()->json(['success' => true, 'count' => 0]);
    }
}
