<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(AddressResource::collection($addresses));
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $address = Address::create($data);

        return $this->created(new AddressResource($address));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        return $this->success(new AddressResource($address));
    }

    public function update(UpdateAddressRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);

        $address->update($data);

        return $this->success(new AddressResource($address->fresh()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $address->delete();
        return $this->success(['success' => true]);
    }

    public function setDefault(Request $request, string $id): JsonResponse
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);

        $address->update(['is_default' => !$address->is_default]);

        return $this->success(new AddressResource($address->fresh()));
    }
}
