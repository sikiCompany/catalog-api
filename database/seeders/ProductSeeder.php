<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('products')->truncate();

        Product::factory()->count(30)->create();

        Product::factory()->electronics()->count(10)->create();

        Product::factory()->inactive()->count(5)->create();

        Product::factory()->active()->count(5)->create();

        $this->command->info('✅ 50 produtos criados com sucesso!');
    }
}
