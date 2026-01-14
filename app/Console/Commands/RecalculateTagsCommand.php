<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\{Dish, Menu, Activity, Room, Ingredient, Product, Tag};

class RecalculateTagsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tags:recalculate 
                       {--type= : Recalculer seulement un type (dish,menu,activity,room,ingredient)}
                       {--global : Recalculer les tags globaux des produits}
                       {--specific : Recalculer les tags spécifiques des modèles}
                       {--force : Forcer le recalcul même si pas de changement}
                       {--create-missing : Créer les tags manquants automatiquement}
                       {--if-needed : Ne lancer que si des modifications ont eu lieu}';

    /**
     * The console command description.
     */
    protected $description = 'Recalcule tous les tags (globaux et spécifiques) pour tous les types de produits';

    /**
     * Configuration des types de produits
     */
    private array $productTypes = [
        'dish' => [
            'model' => Dish::class,
            'label' => 'plats',
            'icon' => '🍽️'
        ],
        'menu' => [
            'model' => Menu::class,
            'label' => 'menus',
            'icon' => '📋'
        ],
        'activity' => [
            'model' => Activity::class,
            'label' => 'activités',
            'icon' => '🚴'
        ],
        'room' => [
            'model' => Room::class,
            'label' => 'chambres',
            'icon' => '🏠'
        ],
        'ingredient' => [
            'model' => Ingredient::class,
            'label' => 'ingrédients',
            'icon' => '🥕'
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // Vérifier si recalcul nécessaire
        if ($this->option('if-needed') && !$this->hasRecentChanges()) {
            $this->info('🏷️  Aucune modification détectée, recalcul ignoré.');
            return 0;
        }

        $this->info('🏷️  Recalcul des tags automatiques...');

        $type = $this->option('type');
        $globalOnly = $this->option('global');
        $specificOnly = $this->option('specific');
        $force = $this->option('force');
        $createMissing = $this->option('create-missing');

        // Validation des options
        if ($type && !isset($this->productTypes[$type])) {
            $this->error("Type '{$type}' non reconnu. Types disponibles: " . implode(', ', array_keys($this->productTypes)));
            return 1;
        }

        // Si aucune option spécifique, faire les deux
        if (!$globalOnly && !$specificOnly) {
            $globalOnly = $specificOnly = true;
        }

        // Recalcul des tags globaux (sur les produits)
        if ($globalOnly && !$type) {
            $this->recalculateGlobalTags($force);
        }

        // Recalcul des tags spécifiques
        if ($specificOnly) {
            if ($type) {
                $this->recalculateSpecificTagsForType($type, $force, $createMissing);
            } else {
                foreach ($this->productTypes as $typeKey => $config) {
                    $this->recalculateSpecificTagsForType($typeKey, $force, $createMissing);
                }
            }
        }

        $this->newLine();
        $this->info('✅ Recalcul terminé !');

        // Mettre à jour le timestamp du dernier recalcul
        cache()->put('tags:last_recalculate', now(), now()->addDays(30));
        
        return 0;
    }

    /**
     * Recalculer les tags globaux des produits
     */
    private function recalculateGlobalTags(bool $force = false): void
    {
        $this->info('🌍 Recalcul des tags globaux des produits...');

        // Cette partie dépend de votre logique métier pour les tags globaux
        // Par exemple, tags basés sur la catégorie, le prix, etc.

        $products = Product::with('globalTags')->get();
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $updated = 0;

        foreach ($products as $product) {
            $oldTags = $product->globalTags()->pluck('name')->toArray();
            $newTags = $this->calculateGlobalTags($product);

            if ($force || $oldTags !== $newTags) {
                // Synchroniser les nouveaux tags globaux
                $globalTagIds = Tag::whereIn('name', $newTags)
                    ->where('is_global', true)
                    ->pluck('id');

                $product->globalTags()->sync($globalTagIds);
                $updated++;

                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->line("  📦 {$product->name}");
                    $this->line("     Avant: " . implode(', ', $oldTags));
                    $this->line("     Après: " . implode(', ', $newTags));
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("   ✅ {$updated} produits mis à jour sur {$products->count()}");
    }

    /**
     * Calculer les tags globaux d'un produit
     */
    private function calculateGlobalTags(Product $product): array
    {
        $tags = [];

        // Tags basés sur le prix
        if ($product->price <= 20) {
            $tags[] = 'budget';
        } elseif ($product->price <= 50) {
            $tags[] = 'standard';
        } else {
            $tags[] = 'premium';
        }

        // Tags basés sur le statut
        if ($product->status) {
            $tags[] = 'available';
        } else {
            $tags[] = 'unavailable';
        }

        // Tags basés sur la catégorie
        if ($product->category) {
            $tags[] = strtolower($product->category->name);
        }

        return array_unique($tags);
    }

    /**
     * Recalculer les tags spécifiques pour un type donné
     */
    private function recalculateSpecificTagsForType(string $type, bool $force = false, bool $createMissing = false): void
    {
        $config = $this->productTypes[$type];
        $modelClass = $config['model'];
        $label = $config['label'];
        $icon = $config['icon'];

        $this->info("{$icon} Recalcul des tags des {$label}...");

        // Charger les modèles avec leurs relations nécessaires
        $items = $this->loadModelsWithRelations($modelClass, $type);

        if ($items->isEmpty()) {
            $this->warn("   ⚠️ Aucun(e) {$label} trouvé(e)");
            return;
        }

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $updated = 0;

        foreach ($items as $item) {
            // Vérifier que le modèle a bien la méthode calculateSpecificTags
            if (!method_exists($item, 'calculateSpecificTags')) {
                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->warn("   ⚠️ {$modelClass} n'a pas de méthode calculateSpecificTags()");
                }
                continue;
            }

            $oldTags = method_exists($item, 'specificTags')
                ? $item->specificTags()->pluck('name')->toArray()
                : [];
            $newTags = $item->calculateSpecificTags();

            // Créer les tags manquants si demandé
            if ($createMissing && !empty($newTags)) {
                $this->ensureTagsExist($newTags);
            }

            // Recalculer seulement si changement ou force
            if ($force || $oldTags !== $newTags) {
                if (method_exists($item, 'updateTags')) {
                    $item->updateTags();
                } else {
                    // Fallback manuel
                    $tagIds = Tag::whereIn('name', $newTags)
                        ->where('is_global', false)
                        ->pluck('id');
                    $item->specificTags()->sync($tagIds);
                }

                $updated++;

                if ($this->output->isVerbose()) {
                    $itemName = $item->product->name ?? $item->name ?? "#{$item->id}";
                    $this->newLine();
                    $this->line("  📝 {$itemName}");
                    $this->line("     Avant: " . implode(', ', $oldTags));
                    $this->line("     Après: " . implode(', ', $newTags));
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("   ✅ {$updated} {$label} mis à jour sur {$items->count()}");
    }

    /**
     * Charger les modèles avec leurs relations nécessaires
     */
    private function loadModelsWithRelations(string $modelClass, string $type)
    {
        switch ($type) {
            case 'dish':
                return $modelClass::with(['ingredients', 'product'])->get();
            case 'menu':
                return $modelClass::with(['dishes.ingredients', 'product'])->get();
            case 'activity':
            case 'room':
                return $modelClass::with(['product'])->get();
            case 'ingredient':
                return $modelClass::with(['dishes.product', 'product'])->get();
            default:
                return $modelClass::with(['product'])->get();
        }
    }

    /**
     * S'assurer que tous les tags existent en base
     */
    private function ensureTagsExist(array $tagNames): void
    {
        foreach ($tagNames as $tagName) {
            Tag::firstOrCreate(
                ['name' => $tagName, 'is_global' => false],
                [
                    'slug' => Str::slug($tagName),
                    'description' => "Tag auto-généré pour {$tagName}",
                    'icon' => 'fas fa-tag'
                ]
            );
        }
    }

    /**
     * Vérifier si des modifications ont eu lieu depuis le dernier run
     */
    private function hasRecentChanges(): bool
    {
        $lastRun = cache()->get('tags:last_recalculate');

        if (!$lastRun) {
            return true;
        }

        // Vérifier si des produits ont été modifiés depuis le dernier run
        $hasChanges = Product::where('updated_at', '>', $lastRun)->exists();

        if (!$hasChanges) {
            foreach ($this->productTypes as $config) {
                if ($config['model']::where('updated_at', '>', $lastRun)->exists()) {
                    $hasChanges = true;
                    break;
                }
            }
        }

        return $hasChanges;
    }
}
