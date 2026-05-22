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
use Illuminate\Validation\ValidationException;

class ProductProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return DB::transaction(function () use ($data, $operation, $uriVariables, $context) {
            try {
                // 1) Récupérer le payload depuis la request
                $payload = $this->getDataFromGlobalRequest();

                Log::info('ProductProcessor - Payload reçu', [
                    'payload' => $payload,
                    'operation' => get_class($operation)
                ]);

                // 2) Créer ProductData 
                $productData = ProductData::from($payload);

                Log::info('ProductProcessor - ProductData créé', [
                    'productData' => $productData->toArray()
                ]);

                // 3) Traitement business logic
                $isNew = $operation instanceof Post;
                $product = $isNew
                    ? $this->createProduct($productData)
                    : $this->updateProduct($productData, (int) $uriVariables['id']);

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
     * Lire directement depuis la Request globale
     * Contourne les problèmes avec API Platform context
     */
    private function getDataFromGlobalRequest(): array
    {
        $request = request(); // Helper Laravel pour la Request globale
        $contentType = $request->header('Content-Type', '');

        Log::info('=== DIRECT Request Reading ===', [
            'method' => $request->method(),
            'content_type' => $contentType,
            'has_files' => $request->hasFile('image'),
            'all_keys' => array_keys($request->all()),
            'file_keys' => array_keys($request->allFiles())
        ]);

        // CAS MULTIPART avec fichier
        if (str_contains($contentType, 'multipart/form-data')) {
            Log::info('📁 Traitement multipart direct');

            $payload = [];

            // Extraire le JSON payload
            if ($request->has('payload')) {
                $payloadString = $request->input('payload');
                Log::info('📝 Payload string reçu', [
                    'content' => $payloadString,
                    'length' => strlen($payloadString)
                ]);

                $decoded = json_decode($payloadString, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
                    Log::info('✅ Payload JSON décodé', ['keys' => array_keys($payload)]);
                } else {
                    Log::error('❌ Erreur décodage JSON', [
                        'error' => json_last_error_msg(),
                        'content' => $payloadString
                    ]);
                }
            }

            // Traiter le fichier image
            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');
                Log::info('📁 Fichier image détecté', [
                    'name' => $imageFile->getClientOriginalName(),
                    'size' => $imageFile->getSize(),
                    'type' => $imageFile->getMimeType()
                ]);

                // Sauvegarder et ajouter l'URL au payload
                $imagePath = $imageFile->store('products', 'public');
                $payload['image'] = '/storage/' . $imagePath;

                Log::info('✅ Image sauvée', ['url' => $payload['image']]);
            }

            if (empty($payload)) {
                throw new \InvalidArgumentException('Aucune donnée valide dans la requête multipart');
            }

            return $this->normalizePayloadFromFrontend($payload);
        }

        // CAS JSON classique
        else {
            $content = $request->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    Log::info('✅ JSON direct décodé');
                    return $this->normalizePayloadFromFrontend($decoded);
                }
            }
        }

        throw new \InvalidArgumentException('Impossible de récupérer les données de la request HTTP');
    }

    private function normalizePayloadFromFrontend(array $payload): array
    {
        Log::info('🔧 Normalisation payload', [
            'payload_keys' => array_keys($payload),
            'payload_sample' => array_slice($payload, 0, 3, true)
        ]);

        if (empty($payload)) {
            throw new \InvalidArgumentException('Payload vide reçu');
        }

        $normalized = [];

        // CHAMPS DE BASE
        $normalized['name'] = $payload['name'] ?? null;
        $normalized['description'] = $payload['description'] ?? null;
        $normalized['price'] = isset($payload['price']) ? (float) $payload['price'] : null;
        $normalized['status'] = isset($payload['status']) ? (bool) $payload['status'] : null;
        $normalized['is_draft'] = isset($payload['is_draft']) ? (bool) $payload['is_draft'] : (isset($payload['isDraft']) ? (bool) $payload['isDraft'] : null);

        // CATÉGORIES
        $normalized['category_id'] = $this->extractCategoryId($payload);

        // CATEGORIES (multi)
        if (isset($payload['categories']) && is_array($payload['categories'])) {
            $normalized['categories'] = $payload['categories'];
        }

        // IMAGE
        if (isset($payload['image']) && is_string($payload['image'])) {
            $normalized['image'] = $payload['image'];
        } else {
            $normalized['image'] = null;
        }

        // PRODUCTABLE TYPE
        $normalized['productable_type'] = $payload['productable_type']
            ?? $payload['productableType']
            ?? null;

        // PRODUCTABLE DATA
        if (isset($payload['relations']) && is_array($payload['relations'])) {
            $normalized['relations'] = $payload['relations'];
        }

        Log::info('🎯 Payload normalisé', [
            'normalized_keys' => array_keys($normalized),
            'productable_type' => $normalized['productable_type']
        ]);

        return $normalized;
    }

    /**
     * Extrait l'ID de catégorie depuis différents formats
     */
    private function extractCategoryId(array $payload): ?int
    {
        // Format direct: category_id ou categoryId
        if (isset($payload['category_id']) && is_numeric($payload['category_id'])) {
            return (int) $payload['category_id'];
        }
        if (isset($payload['categoryId']) && is_numeric($payload['categoryId'])) {
            return (int) $payload['categoryId'];
        }

        // Format IRI: "/api/categories/1"
        $category = $payload['category'] ?? null;
        if (is_string($category) && preg_match('/\/categories\/(\d+)$/', $category, $matches)) {
            return (int) $matches[1];
        }

        // Format tableau de catégories (prendre la première)
        if (isset($payload['categories']) && is_array($payload['categories']) && !empty($payload['categories'])) {
            $firstCategory = $payload['categories'][0];
            if (is_string($firstCategory) && preg_match('/\/categories\/(\d+)$/', $firstCategory, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function createProduct(ProductData $data): Product
    {
        Log::info('Création du produit avec ProductData', ['product_data' => $data->toArray()]);

        // Créer l'entité productable en utilisant logique ProductableData
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

        return $product->loadMissing(['category', 'categories', 'productable', 'globalTags', 'options']);
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

        // Mettre à jour le productable uniquement si des données ont été fournies
        if ($product->productable && $data->productable !== null) {
            $this->updateProductable($product->productable, $data->productable, $data->productableType);
        }

        // Gérer les relations
        $this->handleRelations($product, $product->productable, $data);

        return $product->loadMissing(['category', 'categories', 'productable', 'globalTags', 'options']);
    }

    private function createProductable(string $type, $productableData): mixed
    {
        // Valider avant de traiter
        if (empty($type)) {
            throw ValidationException::withMessages([
                'productableType' => ['Le type de produit est requis']
            ]);
        }

        return match ($type) {
            'App\\Models\\Activity' => Activity::create($productableData->toActivityArray()),
            'App\\Models\\Room' => Room::create($productableData->toRoomArray()),
            'App\\Models\\Menu' => Menu::create([]),
            'App\\Models\\Dish' => Dish::create([]),
            'App\\Models\\Ingredient' => Ingredient::create($productableData->toIngredientArray()),
            default => throw ValidationException::withMessages([
                'productableType' => ["Type de produit non supporté: {$type}"]
            ])
        };
    }

    private function updateProductable(mixed $productable, $productableData, string $type): void
    {
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
        ];
    }

    private function handleRelations(Product $product, mixed $productable, ProductData $data): void
    {

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

        // Categories (multi)
        if (!empty($data->categories)) {
            $categoryIds = $this->extractIds($data->categories);
            $product->categories()->sync($categoryIds);
            Log::info("Categories mises à jour", [
                'product_id' => $product->id,
                'category_ids' => $categoryIds
            ]);
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
