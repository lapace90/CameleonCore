<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // public function index(Request $request)
    // {
    //     try {
    //         $query = Product::with(['category', 'productable', 'globalTags']);

    //         // Filtre par type de productable
    //         if ($request->filled('type')) {
    //             $type = $request->input('type');
    //             $typeMap = [
    //                 'activity' => 'App\\Models\\Activity',
    //                 'room' => 'App\\Models\\Room',
    //                 'menu' => 'App\\Models\\Menu',
    //                 'dish' => 'App\\Models\\Dish',
    //                 'ingredient' => 'App\\Models\\Ingredient',
    //                 'option' => 'App\\Models\\Option'
    //             ];

    //             if (array_key_exists($type, $typeMap)) {
    //                 $query->where('productable_type', '=', $typeMap[$type]);
    //             }
    //         }

    //         // Filtre par statut
    //         if ($request->has('status') && $request->status) {
    //             switch ($request->status) {
    //                 case 'active':
    //                     $query->where('status', true)->where('is_draft', false);
    //                     break;
    //                 case 'inactive':
    //                     $query->where('status', false);
    //                     break;
    //                 case 'draft':
    //                     $query->where('is_draft', true);
    //                     break;
    //             }
    //         }

    //         // Recherche
    //         if ($request->has('search') && $request->search) {
    //             $search = $request->search;
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('name', 'like', "%{$search}%")
    //                     ->orWhere('description', 'like', "%{$search}%");
    //             });
    //         }

    //         // Filtre par catégorie
    //         if ($request->has('category_id') && $request->category_id) {
    //             $query->where('category_id', $request->category_id);
    //         }

    //         // Pagination
    //         $perPage = $request->get('per_page', 20);
    //         $products = $query->paginate($perPage);

    //         // Transformer les produits pour inclure les données productable
    //         $transformedProducts = $products->getCollection()->map(function ($product) {
    //             $productData = $product->toArray();

    //             // Ajouter les données productable complètes
    //             if ($product->productable) {
    //                 $productData['productableData'] = $product->productable->toArray();
    //             } else {
    //                 $productData['productableData'] = null;
    //             }

    //             return $productData;
    //         });

    //         // Format response for API Platform compatibility
    //         $response = [
    //             '@context' => '/api/contexts/Product',
    //             '@id' => '/api/products',
    //             '@productableType' => 'Collection',
    //             'totalItems' => $products->total(),
    //             'member' => $transformedProducts->toArray(),
    //             'view' => [
    //                 '@id' => "/api/products?page={$products->currentPage()}",
    //                 '@productableType' => 'PartialCollectionView',
    //                 'first' => '/api/products?page=1',
    //                 'last' => "/api/products?page={$products->lastPage()}",
    //             ]
    //         ];

    //         if ($products->hasMorePages()) {
    //             $response['view']['next'] = "/api/products?page=" . ($products->currentPage() + 1);
    //         }

    //         if ($products->currentPage() > 1) {
    //             $response['view']['previous'] = "/api/products?page=" . ($products->currentPage() - 1);
    //         }

    //         return response()->json($response);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching products: ' . $e->getMessage());
    //         return response()->json([
    //             'error' => 'Failed to fetch products',
    //             'message' => $e->getMessage()
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

public function index(Request $request)
{
    $debug = [
        'request_type' => $request->input('type'),
        'has_type' => $request->has('type'),
        'all_params' => $request->all(),
        'menu_count' => Product::where('productable_type', 'App\\Models\\Menu')->count(),
        'total_count' => Product::count()
    ];
    
    return response()->json($debug);
}

    /**
     * Store a newly created product in storage.
     *
     * @param  \App\Http\Requests\StoreProductRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProductRequest $request)
    {
        try {
            $data = $request->validated();

            // Handle productable creation if needed
            // This depends on your specific implementation

            $product = Product::create($data);

            // Handle tags or other relationships if needed
            if ($request->has('tags')) {
                $product->globalTags()->attach($request->input('tags'));
            }

            // Reload with relationships
            $product->load(['category', 'productable', 'globalTags']);

            return response()->json($product, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create product',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified product.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Product $product)
    {
        try {
            $product->load([
                'category',
                'productable',
                'globalTags',
                'options'
            ]);

            return response()->json($product);
        } catch (\Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch product',
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \App\Http\Requests\UpdateProductRequest  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            $data = $request->validated();
            $product->update($data);

            // Handle tags or other relationships if needed
            if ($request->has('tags')) {
                $product->globalTags()->sync($request->input('tags'));
            }

            // Reload with relationships
            $product->load(['category', 'productable', 'globalTags']);

            return response()->json($product);
        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update product',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update specific fields of a product (for PATCH requests)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function patch(Request $request, Product $product)
    {
        try {
            // Validate only the fields that are being updated
            $allowedFields = ['status', 'isDraft', 'name', 'description', 'price'];
            $data = $request->only($allowedFields);

            // Validate the data
            $request->validate([
                'status' => 'sometimes|boolean',
                'isDraft' => 'sometimes|boolean',
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0'
            ]);

            $product->update($data);
            $product->load(['category', 'productable', 'globalTags']);

            return response()->json($product);
        } catch (\Exception $e) {
            Log::error('Error patching product: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update product',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $product)
    {
        try {
            $product->delete();
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete product',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search for products based on query parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('query');

            if (!$query) {
                return response()->json([
                    'error' => 'Query parameter is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            $products = Product::with(['category', 'productable', 'globalTags'])
                ->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(50) // Limit search results
                ->get();

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Error searching products: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to search products',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get products statistics
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        try {
            $query = Product::query();

            // Apply same filters as index method
            if ($request->has('type') && $request->type) {
                $type = $request->type;
                $typeMap = [
                    'activity' => 'App\\Models\\Activity',
                    'room' => 'App\\Models\\Room',
                    'menu' => 'App\\Models\\Menu',
                    'dish' => 'App\\Models\\Dish',
                    'option' => 'App\\Models\\Option',
                    'ingredient' => 'App\\Models\\Ingredient'
                ];

                if (isset($typeMap[$type])) {
                    $query->where('productable_type', $typeMap[$type]);
                }
            }

            $stats = [
                'total' => $query->count(),
                'active' => $query->where('status', true)->where('isDraft', false)->count(),
                'inactive' => $query->where('status', false)->count(),
                'draft' => $query->where('isDraft', true)->count(),
                'average_price' => $query->avg('price') ?: 0
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch statistics',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
