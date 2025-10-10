<?php
// database/seeders/ReservationTestSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;

class ReservationTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏨 Création de réservations de test...');

        // Récupérer les produits par type
        $rooms = Product::where('productable_type', 'App\Models\Room')
            ->where('status', true)
            ->limit(3)
            ->get();

        $activities = Product::where('productable_type', 'App\Models\Activity')
            ->where('status', true)
            ->limit(4)
            ->get();

        $menus = Product::where('productable_type', 'App\Models\Menu')
            ->where('status', true)
            ->limit(3)
            ->get();

        if ($rooms->isEmpty() || $activities->isEmpty() || $menus->isEmpty()) {
            $this->command->warn('⚠️ Pas assez de produits disponibles. Lancez ProductSeeder d\'abord.');
            return;
        }

        // Créer ou récupérer des clients
        $customers = [];
        $customerData = [
            ['name' => 'Jean', 'last_name' => 'Dupont', 'email' => 'jean.dupont@test.fr', 'phone' => '0601020304'],
            ['name' => 'Marie', 'last_name' => 'Martin', 'email' => 'marie.martin@test.fr', 'phone' => '0602030405'],
            ['name' => 'Pierre', 'last_name' => 'Bernard', 'email' => 'pierre.bernard@test.fr', 'phone' => '0603040506'],
            ['name' => 'Sophie', 'last_name' => 'Dubois', 'email' => 'sophie.dubois@test.fr', 'phone' => '0604050607'],
            ['name' => 'Lucas', 'last_name' => 'Thomas', 'email' => 'lucas.thomas@test.fr', 'phone' => '0605060708'],
        ];

        foreach ($customerData as $data) {
            $customers[] = Customer::firstOrCreate(
                ['email' => $data['email']],
                $data
            );
        }

        // Scénarios de réservations
        $scenarios = [
            [
                'name' => 'Réservation famille complète',
                'customer' => $customers[0],
                'checkin' => Carbon::now()->addDays(15),
                'checkout' => Carbon::now()->addDays(18),
                'adults' => 2,
                'children' => 2,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'stripe_card',
                'products' => [
                    ['product' => $rooms[0], 'quantity' => 1],
                    ['product' => $activities[0], 'quantity' => 4],
                    ['product' => $activities[1], 'quantity' => 2],
                    ['product' => $menus[0], 'quantity' => 6],
                    ['product' => $menus[1], 'quantity' => 2],
                ]
            ],
            [
                'name' => 'Réservation couple romantique',
                'customer' => $customers[1],
                'checkin' => Carbon::now()->addDays(30),
                'checkout' => Carbon::now()->addDays(33),
                'adults' => 2,
                'children' => 0,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'card',
                'products' => [
                    ['product' => $rooms[1], 'quantity' => 1],
                    ['product' => $activities[2], 'quantity' => 2],
                    ['product' => $menus[2], 'quantity' => 4],
                ]
            ],
            [
                'name' => 'Réservation groupe',
                'customer' => $customers[2],
                'checkin' => Carbon::now()->addDays(45),
                'checkout' => Carbon::now()->addDays(49),
                'adults' => 4,
                'children' => 1,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => 'transfer',
                'products' => [
                    ['product' => $rooms[0], 'quantity' => 2],
                    ['product' => $rooms[2], 'quantity' => 1],
                    ['product' => $activities[0], 'quantity' => 5],
                    ['product' => $activities[1], 'quantity' => 5],
                    ['product' => $activities[3], 'quantity' => 3],
                    ['product' => $menus[0], 'quantity' => 10],
                ]
            ],
            [
                'name' => 'Réservation simple',
                'customer' => $customers[3],
                'checkin' => Carbon::now()->addDays(60),
                'checkout' => Carbon::now()->addDays(62),
                'adults' => 1,
                'children' => 0,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'products' => [
                    ['product' => $rooms[2], 'quantity' => 1],
                    ['product' => $activities[1], 'quantity' => 1],
                    ['product' => $menus[1], 'quantity' => 2],
                ]
            ],
            [
                'name' => 'Réservation longue durée',
                'customer' => $customers[4],
                'checkin' => Carbon::now()->addDays(75),
                'checkout' => Carbon::now()->addDays(82),
                'adults' => 2,
                'children' => 1,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'stripe_card',
                'products' => [
                    ['product' => $rooms[1], 'quantity' => 1],
                    ['product' => $activities[0], 'quantity' => 3],
                    ['product' => $activities[2], 'quantity' => 3],
                    ['product' => $activities[3], 'quantity' => 2],
                    ['product' => $menus[0], 'quantity' => 15],
                    ['product' => $menus[2], 'quantity' => 6],
                ]
            ],
            [
                'name' => 'Réservation annulée (test)',
                'customer' => $customers[0],
                'checkin' => Carbon::now()->addDays(90),
                'checkout' => Carbon::now()->addDays(93),
                'adults' => 2,
                'children' => 0,
                'status' => 'cancelled',
                'payment_status' => 'pending',
                'payment_method' => null,
                'products' => [
                    ['product' => $rooms[0], 'quantity' => 1],
                ]
            ],
        ];

        foreach ($scenarios as $scenario) {
            // Calculer le montant total
            $totalAmount = 0;
            foreach ($scenario['products'] as $productData) {
                $totalAmount += $productData['product']->price * $productData['quantity'];
            }
            
            // Ajouter le coût de l'hébergement (prix par nuit)
            $nights = $scenario['checkin']->diffInDays($scenario['checkout']);
            $roomProduct = collect($scenario['products'])->first(fn($p) => 
                $p['product']->productable_type === 'App\Models\Room'
            );
            if ($roomProduct) {
                $totalAmount += $roomProduct['product']->price * $nights * $roomProduct['quantity'];
            }

            // Créer la réservation
            $reservation = Reservation::create([
                'customer_id' => $scenario['customer']->id,
                'product_id' => $roomProduct['product']->id ?? $scenario['products'][0]['product']->id,
                'product_type' => $roomProduct['product']->productable_type ?? 'App\Models\Product',
                'date' => Carbon::now(),
                'checkin' => $scenario['checkin'],
                'checkout' => $scenario['checkout'],
                'amount' => $totalAmount,
                'booking_source' => 'website',
                'payment_status' => $scenario['payment_status'],
                'payment_method' => $scenario['payment_method'],
                'number_of_adults' => $scenario['adults'],
                'number_of_children' => $scenario['children'],
                'status' => $scenario['status'],
                'comment' => $scenario['name'],
            ]);

            // Générer invoice_number
            $reservation->invoice_number = 'RES-' . date('Ymd') . '-' . str_pad($reservation->id, 6, '0', STR_PAD_LEFT);
            $reservation->save();

            // ✅ Synchroniser les produits dans la pivot
            $pivotData = [];
            foreach ($scenario['products'] as $productData) {
                $pivotData[$productData['product']->id] = [
                    'quantity' => $productData['quantity']
                ];
            }
            $reservation->products()->sync($pivotData);

            $this->command->info("✅ {$scenario['name']} créée (ID: {$reservation->id})");
        }

        $this->command->info("🎉 " . count($scenarios) . " réservations créées avec succès !");
    }
}