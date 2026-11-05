<?php

namespace Database\Seeders;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds with realistic milk tea shop bar tickets.
     */
    public function run(): void
    {
        $orders = [
            [
                'customer_name' => 'Cup #201 — Brown Sugar Boba Fresh Milk (Large, 50% Sugar, Less Ice, Extra Pearls)',
                'status' => Order::STATUS_PENDING,
                'created_at' => Carbon::now()->subMinutes(12),
                'updated_at' => Carbon::now()->subMinutes(12),
            ],
            [
                'customer_name' => 'Cup #202 — Okinawa Milk Tea w/ Cream Cheese & Grass Jelly (Regular, 70% Sugar)',
                'status' => Order::STATUS_PREPARING,
                'created_at' => Carbon::now()->subMinutes(8),
                'updated_at' => Carbon::now()->subMinutes(4),
            ],
            [
                'customer_name' => 'Cup #203 — Taro Pearl Milk Tea w/ Egg Pudding & Boba (Large, 100% Sugar, Normal Ice)',
                'status' => Order::STATUS_READY,
                'created_at' => Carbon::now()->subMinutes(6),
                'updated_at' => Carbon::now()->subMinutes(2),
            ],
            [
                'customer_name' => 'Cup #204 — Wintermelon Cream Float w/ Nata de Coco (Large, 25% Sugar, No Ice)',
                'status' => Order::STATUS_COMPLETED,
                'created_at' => Carbon::now()->subMinutes(20),
                'updated_at' => Carbon::now()->subMinutes(7),
            ],
            [
                'customer_name' => 'Cup #205 — Uji Matcha Green Tea Latte w/ Brown Sugar Pearls (Regular, Less Ice)',
                'status' => Order::STATUS_PENDING,
                'created_at' => Carbon::now()->subMinutes(3),
                'updated_at' => Carbon::now()->subMinutes(3),
            ],
        ];

        foreach ($orders as $order) {
            Order::create($order);
        }
    }
}
