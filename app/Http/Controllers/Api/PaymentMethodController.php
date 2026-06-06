<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\IzipayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly IzipayService $izipay,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $methods = PaymentMethod::where('user_id', $request->user()->id)
            ->defaultFirst()
            ->get()
            ->map(function (PaymentMethod $m) {
                if ($m->tipo_metodo === 'tarjeta') {
                    $m->makeHidden(['documento', 'detalle_extra']);
                }
                return $m;
            });

        return $this->success($methods);
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'tipo_metodo' => 'required|string|in:tarjeta,yape,plin',
            'titular' => 'required|string|max:255',
            'is_default' => 'sometimes|boolean',
            'ruc_dni' => 'nullable|string|max:20',
            'razon_social' => 'nullable|string|max:255',
            'direccion_fiscal' => 'nullable|string|max:500',
        ];

        if ($request->input('tipo_metodo') === 'tarjeta') {
            $rules['card_token'] = 'required|string|max:255';
            $rules['card_last4'] = 'required|string|size:4';
            $rules['card_brand'] = 'required|string|max:50';
            $rules['card_exp_month'] = 'nullable|string|size:2';
            $rules['card_exp_year'] = 'nullable|string|size:4';
        } else {
            $rules['documento'] = 'required|string|max:20';
            $rules['detalle_extra'] = 'nullable|string|max:255';
        }

        $data = $request->validate($rules);
        $data['user_id'] = $request->user()->id;

        if ($data['is_default'] ?? false) {
            PaymentMethod::where('user_id', $data['user_id'])->update(['is_default' => false]);
        }

        $method = PaymentMethod::create($data);

        if ($method->tipo_metodo === 'tarjeta') {
            $method->makeHidden(['documento', 'detalle_extra']);
        }

        return $this->created($method);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $method = PaymentMethod::where('user_id', $request->user()->id)->findOrFail($id);

        if ($method->tipo_metodo === 'tarjeta') {
            $method->makeHidden(['documento', 'detalle_extra']);
        }

        return $this->success($method);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $method = PaymentMethod::where('user_id', $request->user()->id)->findOrFail($id);

        $rules = [
            'titular' => 'sometimes|string|max:255',
            'is_default' => 'sometimes|boolean',
            'ruc_dni' => 'nullable|string|max:20',
            'razon_social' => 'nullable|string|max:255',
            'direccion_fiscal' => 'nullable|string|max:500',
        ];

        if ($method->tipo_metodo === 'tarjeta') {
            $rules['card_last4'] = 'sometimes|string|size:4';
            $rules['card_brand'] = 'sometimes|string|max:50';
            $rules['card_exp_month'] = 'nullable|string|size:2';
            $rules['card_exp_year'] = 'nullable|string|size:4';
        } else {
            $rules['documento'] = 'sometimes|string|max:20';
            $rules['detalle_extra'] = 'nullable|string|max:255';
        }

        $data = $request->validate($rules);

        if ($data['is_default'] ?? false) {
            PaymentMethod::where('user_id', $request->user()->id)
                ->where('id', '!=', $method->id)
                ->update(['is_default' => false]);
        }

        $method->update($data);

        if ($method->tipo_metodo === 'tarjeta') {
            $method->makeHidden(['documento', 'detalle_extra']);
        }

        return $this->success($method->fresh());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $method = PaymentMethod::where('user_id', $request->user()->id)->findOrFail($id);
        $method->delete();
        return $this->success(['success' => true]);
    }

    public function tokenize(Request $request): JsonResponse
    {
        $user = $request->user();
        $email = $user->email;

        try {
            $session = $this->izipay->createTokenizationSession($email);

            return $this->success([
                'mode'       => $session['mode'],
                'public_key' => $session['public_key'],
                'form_token' => $session['form_token'],
            ]);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage());
        }
    }
}
