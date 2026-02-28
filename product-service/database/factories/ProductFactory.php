<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        $catalog = [
            ['name' => 'Wireless Noise-Cancelling Headphones', 'description' => 'Over-ear Bluetooth headphones with active noise cancellation and 30-hour battery life.'],
            ['name' => 'Mechanical Gaming Keyboard', 'description' => 'RGB backlit keyboard with hot-swappable switches and aluminum frame.'],
            ['name' => 'Ergonomic Office Chair', 'description' => 'Adjustable lumbar support chair with breathable mesh back for all-day comfort.'],
            ['name' => '4K Ultra HD Monitor 27"', 'description' => '27-inch IPS monitor with 4K resolution, HDR support, and USB-C connectivity.'],
            ['name' => 'Stainless Steel Cookware Set', 'description' => '10-piece induction-compatible cookware set with tempered glass lids.'],
            ['name' => 'Smart Home Wi-Fi Router AX3000', 'description' => 'Dual-band Wi-Fi 6 router designed for high-speed streaming and low latency gaming.'],
            ['name' => 'Portable SSD 1TB', 'description' => 'High-speed external SSD with USB 3.2 for fast backups and file transfers.'],
            ['name' => 'Electric Toothbrush Pro', 'description' => 'Rechargeable toothbrush with pressure sensor and multiple brushing modes.'],
            ['name' => 'Air Fryer 5.5L Digital', 'description' => 'Large-capacity air fryer with touch controls and preset cooking programs.'],
            ['name' => 'Smartwatch Fitness Tracker', 'description' => 'Water-resistant smartwatch with heart-rate monitoring and sleep tracking.'],
            ['name' => 'Espresso Machine Compact', 'description' => 'Compact espresso machine with 15-bar pressure and milk frother.'],
            ['name' => 'Robot Vacuum Cleaner', 'description' => 'App-controlled robot vacuum with mapping, scheduling, and auto-recharge.'],
        ];
        $product = $this->faker->randomElement($catalog);

        return [
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 100),
        ];
    }
}
