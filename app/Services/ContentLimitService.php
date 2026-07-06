<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Store;
use Illuminate\Database\Eloquent\Model;

final class ContentLimitService
{
    public function __construct(private readonly PlanService $planService) {}

    /**
     * Verifica que la tienda tenga BioBlog habilitado y no haya excedido su
     * límite semanal para el tipo de contenido dado.
     *
     * @param  class-string<Model>  $modelClass
     * @return string|null Mensaje de error si no está permitido, o null si es válido.
     */
    public function checkBioblogLimit(Store $store, string $modelClass, string $capabilityKey, string $label): ?string
    {
        if (! $this->planService->can($store, 'can_bioblog')) {
            return 'Tu plan actual no incluye BioBlog. Actualiza tu plan para publicar contenido.';
        }

        $limit = $this->planService->limit($store, $capabilityKey);
        if ($limit === -1) {
            return null;
        }

        $count = $modelClass::where('store_id', $store->id)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        if ($count >= $limit) {
            return "Alcanzaste tu límite semanal de {$label} ({$limit}). Actualiza tu plan para publicar más.";
        }

        return null;
    }

    /**
     * Verifica que la tienda no haya excedido su límite semanal de temas en BioForo.
     */
    public function checkForumWeeklyLimit(Store $store): ?string
    {
        $limit = $this->planService->limit($store, 'forum_topics_per_week');
        if ($limit === -1) {
            return null;
        }

        $count = \App\Models\ForumTopic::where('store_id', $store->id)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        if ($count >= $limit) {
            return "Alcanzaste tu límite semanal de temas en BioForo ({$limit}). Actualiza tu plan para publicar más.";
        }

        return null;
    }
}
