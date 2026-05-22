<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Obtenir tous les paramètres
     */
    public function index()
    {
        $settings = [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'cache_enabled' => Cache::getStore() instanceof \Illuminate\Cache\Repository,
            'version' => '1.0.0', // Votre version d'app
        ];

        return response()->json($settings);
    }

    /**
     * Vérifier le statut du mode maintenance
     */
    public function maintenanceStatus()
    {
        return response()->json([
            'maintenance_mode' => app()->isDownForMaintenance(),
            'message' => app()->isDownForMaintenance() ? 'Site en maintenance' : 'Site opérationnel'
        ]);
    }

    /**
     * Activer/désactiver le mode maintenance
     */
    public function maintenance(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'message' => 'nullable|string|max:500',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip'
        ]);

        try {
            if ($request->enabled) {
                // Activer la maintenance
                $options = [];
                
                if ($request->message) {
                    $options['--message'] = $request->message;
                }
                
                if ($request->allowed_ips) {
                    $options['--allow'] = implode(',', $request->allowed_ips);
                }

                Artisan::call('down', $options);
                $message = 'Mode maintenance activé';
            } else {
                // Désactiver la maintenance
                Artisan::call('up');
                $message = 'Mode maintenance désactivé';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'maintenance_mode' => $request->enabled
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mode maintenance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vider le cache
     */
    public function clearCache(Request $request)
    {
        $request->validate([
            'types' => 'nullable|array',
            'types.*' => 'in:config,route,view,cache,event'
        ]);

        $types = $request->get('types', ['config', 'route', 'view', 'cache']);
        $cleared = [];

        try {
            foreach ($types as $type) {
                switch ($type) {
                    case 'config':
                        Artisan::call('config:clear');
                        $cleared[] = 'Configuration';
                        break;
                    
                    case 'route':
                        Artisan::call('route:clear');
                        $cleared[] = 'Routes';
                        break;
                    
                    case 'view':
                        Artisan::call('view:clear');
                        $cleared[] = 'Vues';
                        break;
                    
                    case 'cache':
                        Artisan::call('cache:clear');
                        $cleared[] = 'Cache application';
                        break;
                    
                    case 'event':
                        Artisan::call('event:clear');
                        $cleared[] = 'Événements';
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache vidé avec succès',
                'cleared' => $cleared
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vidage du cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Informations système pour debug
     */
    public function systemInfo()
    {
        return response()->json([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'disk_space' => $this->getDiskSpace(),
            'database_connection' => $this->testDatabaseConnection()
        ]);
    }

    /**
     * Obtenir l'espace disque disponible
     */
    private function getDiskSpace()
    {
        $bytes = disk_free_space(base_path());
        $gb = round($bytes / 1024 / 1024 / 1024, 2);
        
        return [
            'free_space_gb' => $gb,
            'free_space_bytes' => $bytes
        ];
    }

    /**
     * Tester la connexion à la base de données
     */
    private function testDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'connected',
                'driver' => config('database.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}