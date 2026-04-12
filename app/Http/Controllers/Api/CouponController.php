<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Coupon::with('store');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        }

        if ($request->boolean('global_only')) {
            $query->where('is_global', true);
        }

        $coupons = $query->orderBy('created_at', 'desc')->paginate(20);

        return $this->success([
            'data' => CouponResource::collection($coupons),
            'pagination' => [
                'page' => $coupons->currentPage(),
                'perPage' => $coupons->perPage(),
                'total' => $coupons->total(),
                'totalPages' => $coupons->lastPage(),
                'hasMore' => $coupons->hasMorePages(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $coupon = Coupon::with('store')->findOrFail($id);

        return $this->success(new CouponResource($coupon));
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (! $user->hasRole('seller') && ! $user->hasRole('administrator')) {
            return $this->forbidden('No tienes permiso para crear cupones.');
        }

        if (isset($data['store_id'])) {
            $store = $user->stores()->where('stores.id', $data['store_id'])->first();
            if (! $store && ! $user->hasRole('administrator')) {
                return $this->forbidden('No tienes acceso a esta tienda.');
            }
        } elseif ($user->hasRole('seller')) {
            $store = $user->store;
            $data['store_id'] = $store?->id;
        }

        $data['code'] = strtoupper($data['code']);

        $coupon = Coupon::create($data);

        $coupon->load('store');

        return $this->created(new CouponResource($coupon));
    }

    public function update(StoreCouponRequest $request, string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $user = $request->user();

        if (! $user->hasRole('administrator') && $coupon->store_id) {
            $hasAccess = $user->stores()->where('stores.id', $coupon->store_id)->exists();
            if (! $hasAccess) {
                return $this->forbidden('No tienes permiso para editar este cupón.');
            }
        }

        $data = $request->validated();

        if (isset($data['code']) && $data['code'] !== $coupon->code) {
            $data['code'] = strtoupper($data['code']);
        }

        $coupon->update($data);
        $coupon->load('store');

        return $this->success(new CouponResource($coupon));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $user = $request->user();

        if (! $user->hasRole('administrator') && $coupon->store_id) {
            $hasAccess = $user->stores()->where('stores.id', $coupon->store_id)->exists();
            if (! $hasAccess) {
                return $this->forbidden('No tienes permiso para eliminar este cupón.');
            }
        }

        $coupon->delete();

        return $this->success(['message' => 'Cupón eliminado correctamente.']);
    }

    public function validate(Request $request): JsonResponse
    {
        $code = $request->query('code');

        if (! $code) {
            return $this->error('El código del cupón es requerido.', 400);
        }

        $coupon = Coupon::findByCode($code);

        if (! $coupon) {
            return $this->error('El cupón no existe.', 404);
        }

        if (! $coupon->isValid()) {
            return $this->error('El cupón no es válido o ha expirado.', 400);
        }

        $userId = $request->user()?->id;

        if ($userId && ! $coupon->isValidForUser($userId)) {
            return $this->error('Ya has usado este cupón el número máximo de veces.', 400);
        }

        $orderAmount = (float) $request->query('order_amount', 0);
        $discount = $coupon->calculateDiscount($orderAmount);

        return $this->success([
            'valid' => true,
            'coupon' => new CouponResource($coupon),
            'discount' => $discount,
        ]);
    }
}
