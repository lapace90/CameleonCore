<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\State\PermissionCollectionProvider;
use ApiPlatform\Metadata\GetCollection;

class TestPermissionsApi extends Command
{
    protected $signature = 'test:permissions-api';
    protected $description = 'Test the new Permissions API structure';

    public function handle()
    {
        $this->info('🧪 Test de l\'API Permissions refactorisée...');
        
        try {
            $provider = new PermissionCollectionProvider();
            $result = $provider->provide(new GetCollection(), [], ['request' => request()]);
            
            $this->info('✅ Provider fonctionne !');
            $this->line('📊 Résultat:');
            
            if (isset($result['categories'])) {
                $this->line("   - {$result['meta']['total_categories']} catégories trouvées");
                
                foreach ($result['categories'] as $category) {
                    $permCount = count($category['permissions']);
                    $this->line("   - {$category['name']}: {$permCount} permissions");
                }
            }
            
            if (isset($result['stats'])) {
                $stats = $result['stats'];
                $this->line("📈 Stats globales:");
                $this->line("   - Total: {$stats['total_permissions']}");
                $this->line("   - Utilisées: {$stats['used_permissions']}");
                $this->line("   - Usage: {$stats['usage_percentage']}%");
            }
            
            $this->info('🎉 Tout fonctionne parfaitement !');
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
}

// Usage: php artisan test:permissions-api