<?php

use Illuminate\Support\Facades\Route;

// Page d'accueil simple
Route::get('/', [App\Http\Controllers\WelcomeController::class, 'index']);

/*
|--------------------------------------------------------------------------  
| ⚠️ IMPORTANT pour API Platform
|--------------------------------------------------------------------------
|
| N'ajoutez PAS de routes API ici ! Toutes vos entités (Product, Dish, Menu, etc.)
| sont déjà exposées automatiquement par API Platform via les annotations.
|
| Les routes se trouvent à :
| - /api/products (géré par ProductCollectionProvider/ProductProcessor)
| - /api/dishes (géré par les annotations dans Dish.php) 
| - /api/menus, /api/activities, /api/rooms, etc.
|
| Pour ajouter des routes personnalisées, utilisez routes/api.php avec un préfixe
| différent (ex: /api/auth, /api/admin) pour éviter les conflits.
*/

