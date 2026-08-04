<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Services\AuditService;
use App\Http\Resources\MediaResource;
use App\Models\Product;
use App\Models\Service;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class MediaController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Upload media to a product.
     * POST /api/products/{productId}/media
     */
    public function uploadProductMedia(StoreMediaRequest $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        Gate::authorize('update', $product);

        $file = $request->file('file');

        $mimeType = $file->getMimeType();

        $product->clearMediaCollection('images');

        $media = $product->addMedia($file)
            ->toMediaCollection('images');

        $imageUrl = $media->getUrl();
        $product->update(['image' => $imageUrl]);

        $this->auditService->record(
            event: 'media.uploaded',
            module: 'media',
            description: 'Imagen subida al producto ID ' . $productId,
            source: AuditService::SOURCE_WEB,
            metadata: ['product_id' => $productId, 'file_type' => $mimeType, 'media_id' => $media->id],
        );

        return $this->created(['url' => $imageUrl]);
    }

    /**
     * Get product media.
     * GET /api/products/{productId}/media
     */
    public function getProductMedia(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $media = $product->getMedia('images');

        return $this->success(MediaResource::collection($media));
    }

    /**
     * Delete product media.
     * DELETE /api/products/{productId}/media/{mediaId}
     */
    public function deleteProductMedia(int $productId, int $mediaId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        Gate::authorize('update', $product);

        $media = $product->media()->find($mediaId);

        if (! $media) {
            return $this->notFound('Media no encontrado.');
        }

        $media->delete();

        $this->auditService->record(
            event: 'media.deleted',
            module: 'media',
            description: 'Imagen eliminada del producto ID ' . $productId,
            source: AuditService::SOURCE_WEB,
            metadata: ['product_id' => $productId, 'media_id' => $mediaId],
        );

        return $this->success();
    }

    /**
     * Reorder product media.
     * PUT /api/products/{productId}/media/reorder
     */
    public function reorderProductMedia(Request $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        Gate::authorize('update', $product);

        $order = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer'],
        ])['order'];

        foreach ($order as $index => $mediaId) {
            $product->media()->where('id', $mediaId)->update(['order_column' => $index]);
        }

        $this->auditService->record(
            event: 'media.uploaded',
            module: 'media',
            description: 'Orden de imágenes reordenado para producto ID ' . $productId,
            source: AuditService::SOURCE_WEB,
            metadata: ['product_id' => $productId, 'new_order' => $order],
        );

        return $this->success();
    }

    /**
     * Upload store logo.
     * POST /api/stores/{storeId}/media/logo
     */
    public function uploadStoreLogo(StoreMediaRequest $request, int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);

            $file = $request->file('file');

            $store->clearMediaCollection('logo');
            $media = $store->addMedia($file)
                ->preservingOriginal()
                ->toMediaCollection('logo');

            $url = $store->getMedia('logo')->first()?->getUrl() ?? $store->getFirstMediaUrl('logo');

            $this->auditService->record(
                event: 'media.uploaded',
                module: 'media',
                description: 'Logo subido para tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id, 'file_type' => $file->getMimeType()],
            );

            return $this->created(['logo' => $url, 'id' => $media->id]);
        } catch (\Exception $e) {
            \Log::error('Error uploading logo: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir logo', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload store marketplace logo (for product/service cards).
     * POST /api/stores/{storeId}/media/logo-marketplace
     */
    public function uploadStoreMarketplaceLogo(StoreMediaRequest $request, int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);

            $file = $request->file('file');

            $store->clearMediaCollection('logo_marketplace');
            $media = $store->addMedia($file)
                ->preservingOriginal()
                ->toMediaCollection('logo_marketplace');

            $url = $store->getMedia('logo_marketplace')->first()?->getUrl() ?? $store->getFirstMediaUrl('logo_marketplace');

            $this->auditService->record(
                event: 'media.uploaded',
                module: 'media',
                description: 'Logo marketplace subido para tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id, 'file_type' => $file->getMimeType()],
            );

            return $this->created(['logo_marketplace' => $url, 'id' => $media->id]);
        } catch (\Exception $e) {
            \Log::error('Error uploading marketplace logo: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir logo marketplace', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload store banner.
     * POST /api/stores/{storeId}/media/banner
     */
    public function uploadStoreBanner(StoreMediaRequest $request, int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);

            $file = $request->file('file');

            $store->clearMediaCollection('banner');
            $media = $store->addMedia($file)
                ->preservingOriginal()
                ->toMediaCollection('banner');

            $url = $store->getMedia('banner')->first()?->getUrl() ?? $store->getFirstMediaUrl('banner');

            $this->auditService->record(
                event: 'media.uploaded',
                module: 'media',
                description: 'Banner subido para tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id, 'file_type' => $file->getMimeType()],
            );

            return $this->created(['banner' => $url, 'id' => $media->id]);
        } catch (\Exception $e) {
            \Log::error('Error uploading banner: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir banner', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload store banner2.
     * POST /api/stores/{storeId}/media/banner2
     */
    public function uploadStoreBanner2(StoreMediaRequest $request, int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);
            $this->validateMainBannerSlot($store, 2);

            $file = $request->file('file');

            $store->clearMediaCollection('banner2');
            $media = $store->addMedia($file)
                ->preservingOriginal()
                ->toMediaCollection('banner2');

            $url = $store->getMedia('banner2')->first()?->getUrl() ?? $store->getFirstMediaUrl('banner2');

            $this->auditService->record(
                event: 'media.uploaded',
                module: 'media',
                description: 'Banner 2 subido para tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id, 'file_type' => $file->getMimeType()],
            );

            return $this->created(['banner2' => $url, 'id' => $media->id]);
        } catch (\OverflowException $e) {
            return response()->json(['message' => $e->getMessage(), 'upgrade_required' => true], 403);
        } catch (\Exception $e) {
            \Log::error('Error uploading banner2: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir banner2', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload store banner3.
     * POST /api/stores/{storeId}/media/banner3
     */
    public function uploadStoreBanner3(StoreMediaRequest $request, int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);
            $this->validateMainBannerSlot($store, 3);

            $file = $request->file('file');

            $store->clearMediaCollection('banner3');
            $media = $store->addMedia($file)
                ->preservingOriginal()
                ->toMediaCollection('banner3');

            $url = $store->getMedia('banner3')->first()?->getUrl() ?? $store->getFirstMediaUrl('banner3');

            $this->auditService->record(
                event: 'media.uploaded',
                module: 'media',
                description: 'Banner 3 subido para tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id, 'file_type' => $file->getMimeType()],
            );

            return $this->created(['banner3' => $url, 'id' => $media->id]);
        } catch (\OverflowException $e) {
            return response()->json(['message' => $e->getMessage(), 'upgrade_required' => true], 403);
        } catch (\Exception $e) {
            \Log::error('Error uploading banner3: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir banner3', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Validate that the store's plan allows uploading to this main banner slot (1-indexed).
     */
    private function validateMainBannerSlot(Store $store, int $slotNumber): void
    {
        $max = app(\App\Services\PlanService::class)->limit($store, 'max_main_banners');
        if ($max !== -1 && $slotNumber > $max) {
            throw new \OverflowException("Tu plan actual solo permite {$max} banner(s) principal(es). Actualiza tu plan para agregar más.");
        }
    }

    /**
     * Validate max ad banners limit.
     */
    private function validateAdBannersLimit(Store $store): void
    {
        $max = app(\App\Services\PlanService::class)->limit($store, 'max_ad_banners');
        $count = $store->getMedia('ad_banners')->count();
        if ($count >= $max) {
            throw new \OverflowException("Máximo {$max} banners promocionales permitidos.");
        }
    }

    /**
     * Upload store ad banner.
     * POST /api/stores/{storeId}/media/ad-banners
     */
    public function uploadStoreAdBanner(StoreMediaRequest $request, int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);

            $this->validateAdBannersLimit($store);

            $file = $request->file('file');
            // 'horizontal' por defecto: mantiene el comportamiento actual para
            // sellers cuya plantilla no tiene slots laterales.
            $orientation = $request->input('orientation', 'horizontal');

            $media = $store->addMedia($file)
                ->preservingOriginal()
                ->withCustomProperties(['orientation' => $orientation])
                ->toMediaCollection('ad_banners');

            $url = $media->getUrl();

            $this->auditService->record(
                event: 'media.uploaded',
                module: 'media',
                description: 'Banner promocional subido para tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id, 'file_type' => $file->getMimeType(), 'orientation' => $orientation],
            );

            return $this->created([
                'id' => $media->id,
                'url' => $url,
                'orientation' => $orientation,
            ]);
        } catch (\OverflowException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            \Log::error('Error uploading ad banner: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir banner promocional', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete store ad banner.
     * DELETE /api/stores/{storeId}/media/ad-banners/{mediaId}
     */
    public function deleteStoreAdBanner(int $storeId, int $mediaId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);

            $media = $store->media()
                ->where('collection_name', 'ad_banners')
                ->find($mediaId);

            if (! $media) {
                return $this->notFound('Banner promocional no encontrado.');
            }

            $media->delete();

            $this->auditService->record(
                event: 'media.deleted',
                module: 'media',
                description: 'Banner promocional eliminado de tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $mediaId],
            );

            return $this->success();
        } catch (\Exception $e) {
            \Log::error('Error deleting ad banner: '.$e->getMessage());

            return response()->json(['message' => 'Error al eliminar banner promocional'], 500);
        }
    }

    /**
     * Delete store banner (principal).
     * DELETE /api/stores/{storeId}/media/banner
     */
    public function deleteStoreBanner(int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);

            $media = $store->media()
                ->where('collection_name', 'banner')
                ->first();

            if (! $media) {
                return $this->notFound('Banner principal no encontrado.');
            }

            $media->delete();

            $this->auditService->record(
                event: 'media.deleted',
                module: 'media',
                description: 'Banner principal eliminado de tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id],
            );

            return $this->success();
        } catch (\Exception $e) {
            \Log::error('Error deleting banner: '.$e->getMessage());

            return response()->json(['message' => 'Error al eliminar banner principal'], 500);
        }
    }

    /**
     * Delete store banner2 (oferta).
     * DELETE /api/stores/{storeId}/media/banner2
     */
    public function deleteStoreBanner2(int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);

            $media = $store->media()
                ->where('collection_name', 'banner2')
                ->first();

            if (! $media) {
                return $this->notFound('Banner de oferta no encontrado.');
            }

            $media->delete();

            $this->auditService->record(
                event: 'media.deleted',
                module: 'media',
                description: 'Banner de oferta eliminado de tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id],
            );

            return $this->success();
        } catch (\Exception $e) {
            \Log::error('Error deleting banner2: '.$e->getMessage());

            return response()->json(['message' => 'Error al eliminar banner de oferta'], 500);
        }
    }

    /**
     * Delete store banner3.
     * DELETE /api/stores/{storeId}/media/banner3
     */
    public function deleteStoreBanner3(int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);

            $media = $store->media()
                ->where('collection_name', 'banner3')
                ->first();

            if (! $media) {
                return $this->notFound('Banner 3 no encontrado.');
            }

            $media->delete();

            $this->auditService->record(
                event: 'media.deleted',
                module: 'media',
                description: 'Banner 3 eliminado de tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id],
            );

            return $this->success();
        } catch (\Exception $e) {
            \Log::error('Error deleting banner3: '.$e->getMessage());

            return response()->json(['message' => 'Error al eliminar banner3'], 500);
        }
    }

    /**
     * Upload store gallery image.
     * POST /api/stores/{storeId}/media/gallery
     */
    public function uploadStoreGallery(StoreMediaRequest $request, int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            Gate::authorize('update', $store);

            $file = $request->file('file');

            $media = $store->addMedia($file)
                ->preservingOriginal()
                ->toMediaCollection('gallery');

            $url = $media->getUrl();

            $this->auditService->record(
                event: 'media.uploaded',
                module: 'media',
                description: 'Galería actualizada para tienda ID ' . $storeId,
                source: AuditService::SOURCE_WEB,
                metadata: ['store_id' => $storeId, 'media_id' => $media->id, 'file_type' => $file->getMimeType()],
            );

            return $this->created([
                'id' => $media->id,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error uploading gallery: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir imagen de galería', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete store gallery image.
     * DELETE /api/stores/{storeId}/media/gallery/{mediaId}
     */
    public function deleteStoreGallery(int $storeId, int $mediaId): JsonResponse
    {
        $store = Store::findOrFail($storeId);

        Gate::authorize('update', $store);

        $media = $store->media()
            ->where('collection_name', 'gallery')
            ->find($mediaId);

        if (! $media) {
            return $this->notFound('Imagen de galería no encontrada.');
        }

        $media->delete();

        $this->auditService->record(
            event: 'media.deleted',
            module: 'media',
            description: 'Imagen de galería eliminada de tienda ID ' . $storeId,
            source: AuditService::SOURCE_WEB,
            metadata: ['store_id' => $storeId, 'media_id' => $mediaId],
        );

        return $this->success();
    }

    /**
     * Delete store media.
     * DELETE /api/stores/{storeId}/media/{mediaId}
     */
    public function deleteStoreMedia(int $storeId, int $mediaId): JsonResponse
    {
        $store = Store::findOrFail($storeId);

        Gate::authorize('update', $store);

        $media = $store->media()->find($mediaId);

        if (! $media) {
            return $this->notFound('Media no encontrado.');
        }

        $media->delete();

        $this->auditService->record(
            event: 'media.deleted',
            module: 'media',
            description: 'Media eliminado de tienda ID ' . $storeId,
            source: AuditService::SOURCE_WEB,
            metadata: ['store_id' => $storeId, 'media_id' => $mediaId],
        );

        return $this->success();
    }

    /**
     * Upload media to a service.
     * POST /api/services/{serviceId}/media
     */
    public function uploadServiceMedia(StoreMediaRequest $request, int $serviceId): JsonResponse
    {
        try {
            $service = Service::findOrFail($serviceId);

            $user = $request->user();
            $hasAccess = $user->ownedStores()->where('stores.id', $service->store_id)->exists()
                || $user->stores()->where('stores.id', $service->store_id)->exists();
            if (! $hasAccess && ! $user->hasRole('administrator')) {
                return response()->json(['message' => 'No tienes acceso a este servicio'], 403);
            }

            $file = $request->file('file');
            $path = $file->store('services', 'public');
            $url = Storage::url($path);

            $service->update(['image' => $url]);

            $this->auditService->record(
                event: 'media.uploaded',
                module: 'media',
                description: 'Imagen subida para servicio ID ' . $serviceId,
                source: AuditService::SOURCE_WEB,
                metadata: ['service_id' => $serviceId, 'path' => $path],
            );

            return $this->created([
                'url' => $url,
                'path' => $path,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error uploading service image: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir imagen', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload store policy PDF.
     * POST /api/stores/{storeId}/media/policy
     * Body: { "file": <PDF>, "type": "shipping|return|privacy" }
     */
    public function uploadStorePolicy(Request $request, int $storeId): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            if (! $request->user()->hasRole('administrator') && $store->owner_id !== $request->user()->id) {
                return response()->json(['message' => 'No tienes permiso para actualizar esta tienda.'], 403);
            }

            $data = $request->validate([
                'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
                'type' => ['required', 'string', 'in:shipping,return,privacy'],
            ]);

            $file = $data['file'];
            $type = $data['type'];

            $existingMedia = $store->media()
                ->where('collection_name', 'policies')
                ->whereJsonContains('custom_properties->type', $type)
                ->first();

            if ($existingMedia) {
                $existingMedia->delete();
            }

            $store->addMedia($file)
                ->usingFileName("{$type}_policy.pdf")
                ->withCustomProperties(['type' => $type])
                ->toMediaCollection('policies');

            return $this->created([
                'type' => $type,
                'url' => $store->getPolicyUrl($type),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al subir archivo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete store policy.
     * DELETE /api/stores/{storeId}/media/policy/{type}
     */
    public function deleteStorePolicy(Request $request, int $storeId, string $type): JsonResponse
    {
        try {
            $store = Store::findOrFail($storeId);

            if (! $request->user()->hasRole('administrator') && $store->owner_id !== $request->user()->id) {
                return response()->json(['message' => 'No tienes permiso para actualizar esta tienda.'], 403);
            }

            if (! in_array($type, ['shipping', 'return', 'privacy'])) {
                return $this->notFound('Tipo de política no válido.');
            }

            $media = $store->media()
                ->where('collection_name', 'policies')
                ->whereJsonContains('custom_properties->type', $type)
                ->first();

            if (! $media) {
                return $this->notFound('Política no encontrada.');
            }

            $media->delete();

            return $this->success();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar archivo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
