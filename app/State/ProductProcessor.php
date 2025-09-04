<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Product;
use App\Models\Activity;
use App\Models\Menu;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Room;
use App\Data\ProductData;
use App\Data\ProductOutputData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ApiPlatform\Metadata\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return DB::transaction(function () use ($data, $operation, $uriVariables, $context) {
            try {
                // 1) Récupérer le payload depuis la request
                $payload = $this->getDataFromRequest($context);

                Log::info('ProductProcessor - Payload reçu', [
                    'payload' => $payload,
                    'operation' => get_class($operation)
                ]);

                // 2) Créer ProductData avec votre architecture - GARDÉE !
                $productData = ProductData::from($payload);

                Log::info('ProductProcessor - ProductData créé', [
                    'productData' => $productData->toArray()
                ]);

                // 3) Traitement business logic
                $isNew = $operation instanceof Post;
                $product = $isNew
                    ? $this->createProduct($productData)
                    : $this->updateProduct($productData, (int) $uriVariables['id']);

                // 4) SOLUTION : Retourner un ARRAY, pas un objet ProductOutputData
                // Ça évite le problème de sérialisation d'API Platform Laravel
                return ProductOutputData::fromProduct($product);
            } catch (ValidationException $e) {
                Log::error('Erreur de validation', ['errors' => $e->errors(), 'payload' => $payload ?? null]);
                throw $e;
            } catch (\Throwable $e) {
                Log::error('Erreur dans ProductProcessor', [
                    'message' => $e->getMessage(),
                    'payload' => $payload ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Récupère les données depuis la request HTTP
     */
    private function getDataFromRequest(array $context): array
    {
        // Méthode 1: Depuis le contexte API Platform
        if (isset($context['request']) && $context['request'] instanceof Request) {
            $request = $context['request'];

            // Essayer le contenu JSON de la request
            $content = $request->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $this->normalizePayloadFromFrontend($decoded);
                }
            }

            // Fallback sur all()
            $requestData = $request->all();
            if (!empty($requestData)) {
                return $this->normalizePayloadFromFrontend($requestData);
            }
        }

        // Méthode 2: Depuis la request globale
        $request = app(Request::class);
        if ($request) {
            $content = $request->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $this->normalizePayloadFromFrontend($decoded);
                }
            }

            $requestData = $request->all();
            if (!empty($requestData)) {
                return $this->normalizePayloadFromFrontend($requestData);
            }
        }

        throw new \InvalidArgumentException('Impossible de récupérer les données de la request HTTP');
    }

    /**
     * Normalise les données envoyées par le frontend pour correspondre à ProductData
     */
    private function normalizePayloadFromFrontend(array $payload): array
    {
        $normalized = [];

        // Champs directs du produit
        $normalized['name'] = $payload['name'] ?? null;
        $normalized['description'] = $payload['description'] ?? null;
        $normalized['price'] = array_key_exists('price', $payload) ? (float) $payload['price'] : null;
        $normalized['status'] = array_key_exists('status', $payload) ? (bool) $payload['status'] : null;
        $normalized['is_draft'] = array_key_exists('is_draft', $payload)
            ? (bool) $payload['is_draft']
            : (array_key_exists('isDraft', $payload) ? (bool) $payload['isDraft'] : null);
        $normalized['category_id'] = $payload['category_id']
            ?? ($payload['categoryId'] ?? null);
        $normalized['image'] = array_key_exists('image', $payload) ? $payload['image'] : null;

        // Gestion du type productable (frontend envoie "productableType" au lieu de "productable_type")
        $normalized['productable_type'] = $payload['productable_type']
            ?? ($payload['productableType'] ?? null);

        // Traitement de l'objet productable - GARDER votre logique
        $productableData = $payload['productable'] ?? null;

        // Nettoyer les métadonnées API Platform
        if (is_array($productableData)) {
            if (isset($productableData['@context']) || isset($productableData['@id'])) {
                $cleanedData = array_filter($productableData, function ($key) {
                    return !str_starts_with($key, '@');
                }, ARRAY_FILTER_USE_KEY);
                $productableData = $cleanedData;
            }
        }

        $normalized['productable'] = $productableData;

        // Relations, tags, options
        $normalized['relations'] = $payload['relations'] ?? null;
        $normalized['tags'] = $payload['tags'] ?? null;
        $normalized['options'] = $payload['options'] ?? null;

        return $normalized;
    }

    private function createProduct(ProductData $data): Product
    {
        Log::info('Création du produit avec ProductData', ['product_data' => $data->toArray()]);

        // Créer l'entité productable en utilisant VOTRE logique ProductableData
        $productable = $this->createProductable($data->productableType, $data->productable);

        // Créer le produit
        $product = Product::create([
            'name' => $data->name,
            'description' => $data->description ?? '',
            'price' => number_format($data->price, 2, '.', ''),
            'status' => $data->status,
            'is_draft' => $data->isDraft,
            'category_id' => $data->categoryId,
            'productable_type' => get_class($productable),
            'productable_id' => $productable->id,
            'image' => $data->image,
        ]);

        Log::info('Produit créé avec succès', [
            'product_id' => $product->id,
            'productable_type' => get_class($productable),
            'productable_id' => $productable->id
        ]);

        // Gérer les relations avec VOTRE logique
        $this->handleRelations($product, $productable, $data);

        return $product->loadMissing(['category', 'productable', 'globalTags', 'options']);
    }

    private function updateProduct(ProductData $data, int $productId): Product
    {
        $product = Product::findOrFail($productId);

        // Mettre à jour les champs du produit
        $updates = [
            'name' => $data->name,
            'description' => $data->description,
            'price' => number_format($data->price, 2, '.', ''),
            'status' => $data->status,
            'is_draft' => $data->isDraft,
            'category_id' => $data->categoryId,
            'image' => $data->image,
        ];

        $product->update(array_filter($updates, fn($value) => $value !== null));

        // Mettre à jour le productable en utilisant VOTRE logique ProductableData
        if ($product->productable) {
            $this->updateProductable($product->productable, $data->productable, $data->productableType);
        }

        // Gérer les relations
        $this->handleRelations($product, $product->productable, $data);

        return $product->loadMissing(['category', 'productable', 'globalTags', 'options']);
    }

    private function createProductable(string $type, $productableData): mixed
    {
        Log::info('Création du productable', [
            'type' => $type,
            'data' => $productableData
        ]);

        // GARDER votre logique ProductableData !
        return match ($type) {
            'App\\Models\\Activity' => Activity::create($productableData->toActivityArray()),
            'App\\Models\\Room' => Room::create($productableData->toRoomArray()),
            'App\\Models\\Menu' => Menu::create([]),
            'App\\Models\\Dish' => Dish::create([]),
            'App\\Models\\Ingredient' => Ingredient::create($productableData->toIngredientArray()),
            default => throw new \InvalidArgumentException("Type de productable non supporté: {$type}")
        };
    }

    private function updateProductable(mixed $productable, $productableData, string $type): void
    {
        // GARDER votre logique ProductableData !
        $updateData = match ($type) {
            'App\\Models\\Activity' => $productableData->toActivityArray(),
            'App\\Models\\Room' => $productableData->toRoomArray(),
            'App\\Models\\Ingredient' => $productableData->toIngredientArray(),
            'App\\Models\\Menu' => $this->mapMenuData($productableData),
            'App\\Models\\Dish' => $this->mapDishData($productableData),

            default => []
        };
        $updateData = array_filter($updateData, fn($value) => $value !== null);
        if (!empty($updateData)) {
            $productable->update($updateData);
        }
    }

    /**
     * Extrait et normalise les champs spécifiques aux menus.
     */
    private function mapMenuData($productableData): array
    {
        $data = is_array($productableData)
            ? $productableData
            : (array) $productableData;

        return [
            'type' => $data['type'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ];
    }

    /**
     * Extrait et normalise les champs spécifiques aux plats.
     */
    private function mapDishData($productableData): array
    {
        $data = is_array($productableData)
            ? $productableData
            : (array) $productableData;

        return [
            'course' => $data['course'] ?? null,
            'is_vegetarian' => $data['is_vegetarian'] ?? null,
            'is_vegan' => $data['is_vegan'] ?? null,
            'is_spicy' => $data['is_spicy'] ?? null,
            'is_gluten_free' => $data['is_gluten_free'] ?? null,
            'is_lactose_free' => $data['is_lactose_free'] ?? null,
            'is_nut_free' => $data['is_nut_free'] ?? null,
        ];
    }

    private function handleRelations(Product $product, mixed $productable, ProductData $data): void
    {
        // GARDER votre logique de relations !

        // Relations des plats (Dish -> Ingredients)
        if ($productable instanceof Dish && isset($data->relations['ingredients'])) {
            $ingredientIds = $this->extractIds($data->relations['ingredients']);
            $productable->ingredients()->sync($ingredientIds);
            Log::info("Relations plat-ingrédients mises à jour", [
                'dish_id' => $productable->id,
                'ingredient_ids' => $ingredientIds
            ]);
        }

        // Relations des menus (Menu -> Dishes)  
        if ($productable instanceof Menu && isset($data->relations['dishes'])) {
            $dishIds = $this->extractIds($data->relations['dishes']);
            $productable->dishes()->sync($dishIds);
            Log::info("Relations menu-plats mises à jour", [
                'menu_id' => $productable->id,
                'dish_ids' => $dishIds
            ]);
        }

        // Tags globaux
        if (!empty($data->tags)) {
            $tagIds = $this->extractIds($data->tags);
            $product->globalTags()->sync($tagIds);
        }

        // Options du produit
        if (!empty($data->options)) {
            $optionIds = $this->extractIds($data->options);
            $product->options()->sync($optionIds);
        }
    }

    /**
     * Extrait les IDs d'un tableau d'éléments mixtes
     */
    private function extractIds(array $items): array
    {
        return collect($items)->map(function ($item) {
            if (is_int($item)) return $item;
            if (is_string($item) && ctype_digit($item)) return (int) $item;
            if (is_array($item) && isset($item['id'])) return (int) $item['id'];
            if (is_string($item) && preg_match('/(\d+)$/', $item, $matches)) {
                return (int) $matches[1];
            }
            return null;
        })->filter()->unique()->values()->toArray();
    }
}
