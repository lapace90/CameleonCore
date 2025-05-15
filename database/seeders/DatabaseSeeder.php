<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Option;
use App\Models\Ingredient;
use App\Models\User;
use App\Models\Room;  // Assurez-vous que cette ligne est présente
use App\Models\Category;
use App\Models\Activity;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tag;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Base models without dependencies
            $this->seedModel('users', fn() => User::factory()->count(10)->create());
            $this->seedModel('categories', fn() => Category::factory()->count(5)->create());
            $this->seedModel('tags', fn() => Tag::factory()->count(10)->create());
            $this->seedModel('ingredients', fn() => Ingredient::factory()->count(25)->create());

            // Seed polymorphic models first
            $this->seedModel('rooms', fn() => Room::factory()->count(10)->create());
            $this->seedModel('dishes', fn() => Dish::factory()->count(15)->create());
            $this->seedModel('activities', fn() => Activity::factory()->count(10)->create());
            $this->seedModel('menus', fn() => Menu::factory()->count(10)->create());

            // Seed products with polymorphic relations
            $this->seedModel('products', function () {
                $polymorphicModels = [
                    Room::class,
                    Dish::class,
                    Activity::class,
                    Menu::class,
                ];

                Product::factory()->count(45)->make()->each(function ($product) use ($polymorphicModels) {
                    $productableType = $polymorphicModels[array_rand($polymorphicModels)];
                    $productable = $productableType::inRandomOrder()->first();

                    $product->productable_id = $productable->id;
                    $product->productable_type = $productableType;
                    $product->category_id = Category::inRandomOrder()->first()->id;
                    $product->save();
                });
            });


            $this->seedModel('customers', fn() => Customer::factory()->count(10)->create());

            // // Models with complex dependencies
            // $this->seedModel('reservations', function () {
            //     return Reservation::factory()->count(10)->create([
            //         'room_id' => function () {
            //             return Room::inRandomOrder()->first()->id;
            //         },
            //         'customer_id' => function () {
            //             return Customer::inRandomOrder()->first()->id;
            //         },
            //     ]);
            // });

            // $this->seedModel('invoices', function () {
            //     return Invoice::factory()->count(10)->create()->each(function ($invoice) {
            //         $invoice->customer()->associate(Customer::inRandomOrder()->first());
            //         $invoice->reservation()->associate(Reservation::inRandomOrder()->first());
            //         $invoice->save();
            //     });
            // });

            // // Permissions and roles last
            // $this->seedModel('permissions', fn() => Permission::factory()->count(10)->create());
            // $this->seedModel('roles', function () {
            //     return Role::factory()->count(5)->create()->each(function ($role) {
            //         $role->permissions()->attach(Permission::inRandomOrder()->take(3)->pluck('id'));
            //     });
            // });

            DB::commit();
            Log::info('Database seeding completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Database seeding failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }
    /**
     * Seed a model with a callback.
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    private function seedModel(string $name, callable $callback): void
    {
        try {
            Log::info("Seeding {$name}...");
            $callback();
            Log::info("Finished seeding {$name}");
        } catch (\Exception $e) {
            Log::error("Error seeding {$name}: " . $e->getMessage());
            throw $e;
        }
    }
}
