<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Brand;
use App\Models\Center;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $center = Center::create([
            'name' => 'Newal Car Sharing',
        ]);
        \App\Models\User::factory()->create([
            'name' => 'Administrador',
            'email' => 'el.solitions@gmail.com',
            'can_admin_panel' => true,
            'can_show_general_resource' => true,
            'center_id' =>  $center->id,
            'password' => Hash::make("EithanSteven15*"),
        ]);

        $this->call(UnitOfMeasureSeeder::class);
        $this->call(SettingDataSeeder::class);
        $this->call(CategoryDataSeeder::class);
        $this->call(CmsContentSeeder::class);
        $this->call(CarDataSeeder::class);
        /* $this->call(InsertDataSeeder::class);
        $this->call(UnitOfMeasureSeeder::class);
        $this->call(BrandDataSeeder::class);
        $this->call(SupplierDataSeeder::class);
        $this->call(SettingDataSeeder::class);
        $this->call(InsertItemsSeeder::class);
        $this->call(OrderSeeder::class);
        $this->call(OtherExpenseSeeder::class);*/
        $this->call(OtherExpenseItemSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(FuelTypeSeeder::class);
        $this->call(WorldTableSeeder::class);
    }
}
