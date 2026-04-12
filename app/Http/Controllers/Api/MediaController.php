<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class MediaController extends Controller
{
    /**
     * Upload media to a product.
     * POST /api/products/{productId}/media
     */
    public function uploadProductMedia(StoreMediaRequest $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        Gate::authorize('update', $product);

        $file = $request->file('file');

        $media = $product->addMedia($file)
            ->toMediaCollection('images');

        return $this->created(new MediaResource($media));
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

            return $this->created(['logo' => $url, 'id' => $media->id]);
        } catch (\Exception $e) {
            \Log::error('Error uploading logo: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir logo', 'error' => $e->getMessage()], 500);
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

            $file = $request->file('file');

            $store->clearMediaCollection('banner2');
            $media = $store->addMedia($file)
                ->preservingOriginal()
                ->toMediaCollection('banner2');

            $url = $store->getMedia('banner2')->first()?->getUrl() ?? $store->getFirstMediaUrl('banner2');

            return $this->created(['banner2' => $url, 'id' => $media->id]);
        } catch (\Exception $e) {
            \Log::error('Error uploading banner2: '.$e->getMessage());

            return response()->json(['message' => 'Error al subir banner2', 'error' => $e->getMessage()], 500);
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

            $url = $store->getMedia('gallery')->last()?->getUrl() ?? $store->getFirstMediaUrl('gallery');

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

        return $this->success();
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
