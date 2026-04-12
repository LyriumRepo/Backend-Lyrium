<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'email', 'max:255'],
            ], [
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Ingresa un correo electrónico válido.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }

        $email = $request->input('email');

        $existing = NewsletterSubscription::where('email', $email)->first();

        if ($existing) {
            return $this->success(['message' => 'Ya estás suscrito a nuestro newsletter.']);
        }

        NewsletterSubscription::create([
            'email' => $email,
            'subscribed_at' => now(),
        ]);

        return $this->success(['message' => 'Suscrito correctamente.']);
    }
}
