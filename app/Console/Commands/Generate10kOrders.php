<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Generate10kOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:seed-10k {--count=10000 : The number of orders to generate} {--fresh : Whether to truncate the table before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a realistic 10,000+ milk tea test dataset for API performance testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $fresh = (bool) $this->option('fresh');

        $this->info("=== BobaCraft Milk Tea Bar: Safe Test Dataset Generator ===");

        if ($fresh) {
            $this->warn("Truncating existing orders...");
            Order::truncate();
        }

        $currentCount = Order::count();
        $this->info("Current order count: {$currentCount}");

        $flavors = [
            'Brown Sugar Boba Fresh Milk',
            'Okinawa Milk Tea w/ Cream Cheese',
            'Taro Pearl Milk Tea w/ Pudding',
            'Wintermelon Cream Float',
            'Uji Matcha Latte w/ Boba',
            'Thai Milk Tea w/ Grass Jelly',
            'Mango Green Tea w/ Coconut Jelly',
            'Roasted Oolong Milk Tea',
            'Hokkaido Milk Tea w/ Brown Sugar Pearls',
            'Jasmine Green Milk Tea w/ Aloe Vera',
            'Strawberry Milk Tea w/ Popping Boba',
            'Caramel Milk Tea w/ Custard Pudding',
            'Dark Chocolate Hazelnut Milk Tea',
            'Hong Kong Style Silk Milk Tea',
            'Peach Oolong Milk Tea w/ Lychee Jelly',
        ];

        $sugarLevels = ['0% (No Sugar)', '25% (Mild)', '50% (Half)', '70% (Less Sweet)', '100% (Standard)'];
        $iceLevels = ['No Ice', 'Less Ice', 'Standard Ice', 'Extra Ice'];
        $sizes = ['Regular', 'Large'];
        $toppings = ['Extra Boba', 'Egg Pudding', 'Grass Jelly', 'Cheese Foam', 'Nata de Coco', 'Aloe Vera'];

        $firstNames = ['Juan', 'Maria', 'Angela', 'Mark', 'Sofia', 'Liam', 'Noah', 'Emma', 'Olivia', 'Ethan', 'Chloe', 'Daniel', 'Carlos', 'Andrea', 'Gabriel', 'Bea', 'Joshua', 'Patricia', 'Kevin', 'Hannah'];
        $lastNames = ['Dela Cruz', 'Santos', 'Reyes', 'Villanueva', 'Mendoza', 'Tan', 'Lim', 'Garcia', 'Bautista', 'Torres', 'Ramos', 'Aquino', 'Castro', 'Flores', 'Navarro'];

        $statuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PREPARING,
            Order::STATUS_READY,
            Order::STATUS_COMPLETED,
        ];
        // Weighted distribution: 15% pending, 20% preparing, 15% ready, 50% completed
        $statusPool = array_merge(
            array_fill(0, 15, Order::STATUS_PENDING),
            array_fill(0, 20, Order::STATUS_PREPARING),
            array_fill(0, 15, Order::STATUS_READY),
            array_fill(0, 50, Order::STATUS_COMPLETED),
        );

        $chunkSize = 1000;
        $totalChunks = (int) ceil($count / $chunkSize);
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $now = Carbon::now();
        $startTime = microtime(true);

        for ($c = 0; $c < $totalChunks; $c++) {
            $batch = [];
            $batchLimit = min($chunkSize, $count - ($c * $chunkSize));

            for ($i = 0; $i < $batchLimit; $i++) {
                $orderNum = $currentCount + ($c * $chunkSize) + $i + 1;
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $flavor = $flavors[array_rand($flavors)];
                $sugar = $sugarLevels[array_rand($sugarLevels)];
                $ice = $iceLevels[array_rand($iceLevels)];
                $size = $sizes[array_rand($sizes)];
                $topping = $toppings[array_rand($toppings)];
                $status = $statusPool[array_rand($statusPool)];

                // Distributed creation time across last 30 days
                $minutesAgo = rand(1, 43200);
                $createdAt = $now->copy()->subMinutes($minutesAgo);
                $updatedAt = $createdAt->copy()->addMinutes(rand(1, 15));

                $ticket = sprintf(
                    "Cup #%05d — %s (%s, %s, %s, %s) [%s %s]",
                    $orderNum,
                    $flavor,
                    $size,
                    $sugar,
                    $ice,
                    $topping,
                    $firstName,
                    $lastName
                );

                $batch[] = [
                    'customer_name' => $ticket,
                    'status' => $status,
                    'created_at' => $createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
                ];
            }

            DB::table('orders')->insert($batch);
            $bar->advance($batchLimit);
        }

        $bar->finish();
        $this->newLine(2);

        $elapsed = round(microtime(true) - $startTime, 2);
        $newTotal = Order::count();

        $this->info("Successfully generated {$count} test records in {$elapsed}s!");
        $this->info("Total orders currently in database: {$newTotal}");
        $this->table(
            ['Status', 'Count'],
            [
                ['Pending', Order::where('status', Order::STATUS_PENDING)->count()],
                ['Preparing', Order::where('status', Order::STATUS_PREPARING)->count()],
                ['Ready', Order::where('status', Order::STATUS_READY)->count()],
                ['Completed', Order::where('status', Order::STATUS_COMPLETED)->count()],
            ]
        );

        return self::SUCCESS;
    }
}
