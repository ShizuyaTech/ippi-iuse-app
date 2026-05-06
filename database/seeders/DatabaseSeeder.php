<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkCenter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Users
        User::firstOrCreate(['email' => 'admin@ippi.com'], ['name' => 'Administrator', 'password' => Hash::make('ippi54321'), 'role' => 'admin']);
        User::firstOrCreate(['email' => 'planner@ippi.com'], ['name' => 'Planner PP', 'password' => Hash::make('ippi54321'), 'role' => 'planner']);
        User::firstOrCreate(['email' => 'purchasing@ippi.com'], ['name' => 'Purchasing MM', 'password' => Hash::make('ippi54321'), 'role' => 'purchasing']);
        User::firstOrCreate(['email' => 'warehouse@ippi.com'], ['name' => 'Warehouse Staff', 'password' => Hash::make('ippi54321'), 'role' => 'warehouse']);

        // Storage Locations – updateOrCreate agar material_type ikut diset meski record sudah ada
        StorageLocation::updateOrCreate(
            ['code' => 'I101'],
            ['name' => 'Gudang IRM', 'description' => 'Penyimpanan material RM', 'material_type' => 'RM']
        );
        StorageLocation::updateOrCreate(
            ['code' => 'I100'],
            ['name' => 'Gudang WIP', 'description' => 'Work-in-Process', 'material_type' => 'WIP']
        );
        StorageLocation::updateOrCreate(
            ['code' => 'I107'],
            ['name' => 'Gudang Logistik', 'description' => 'Penyimpanan FP', 'material_type' => 'FP']
        );

        // Materials
        // Material::firstOrCreate(['code' => 'RM-001'], ['name' => 'Baja Plat 3mm', 'type' => 'RM', 'unit_of_measure' => 'Kg', 'standard_price' => 15000]);
        // Material::firstOrCreate(['code' => 'RM-002'], ['name' => 'Aluminium Batang', 'type' => 'RM', 'unit_of_measure' => 'Kg', 'standard_price' => 35000]);
        // Material::firstOrCreate(['code' => 'RM-003'], ['name' => 'Baut M8x30', 'type' => 'RM', 'unit_of_measure' => 'SHT', 'standard_price' => 500]);
        // Material::firstOrCreate(['code' => 'RM-004'], ['name' => 'Cat Powder Coating', 'type' => 'RM', 'unit_of_measure' => 'Kg', 'standard_price' => 45000]);
        // Material::firstOrCreate(['code' => 'WIP-001'], ['name' => 'Rangka Besi Sub-assy', 'type' => 'WIP', 'unit_of_measure' => 'PCS', 'standard_price' => 120000]);
        // Material::firstOrCreate(['code' => 'FP-001'], ['name' => 'Meja Besi Industrial', 'type' => 'FP', 'unit_of_measure' => 'PCS', 'standard_price' => 850000]);
        // Material::firstOrCreate(['code' => 'FP-002'], ['name' => 'Rak Gudang 5 Tingkat', 'type' => 'FP', 'unit_of_measure' => 'PCS', 'standard_price' => 1250000]);

        // Vendors
        // Vendor::firstOrCreate(['code' => 'VND-001'], ['name' => 'PT Baja Nusantara', 'contact_person' => 'Budi Santoso', 'email' => 'sales@bajanusantara.co.id', 'phone' => '021-55123456', 'address' => 'Jl. Industri No.1, Jakarta', 'is_active' => true]);
        // Vendor::firstOrCreate(['code' => 'VND-002'], ['name' => 'CV Logam Prima', 'contact_person' => 'Siti Rahayu', 'email' => 'info@logamprima.com', 'phone' => '031-7654321', 'address' => 'Jl. Pahlawan No.5, Surabaya', 'is_active' => true]);
        // Vendor::firstOrCreate(['code' => 'VND-003'], ['name' => 'UD Cat Warna Indah', 'contact_person' => 'Ahmad Fauzi', 'email' => 'order@catwarnaindah.com', 'phone' => '022-3456789', 'address' => 'Jl. Merdeka No.10, Bandung', 'is_active' => true]);

        // Work Centers
        WorkCenter::firstOrCreate(['code' => 'WC-CUT'], ['name' => 'Mesin Cutting', 'description' => 'Pemotongan material', 'capacity_per_hour' => 50, 'cost_per_hour' => 75000, 'is_active' => true]);
        WorkCenter::firstOrCreate(['code' => 'WC-WLD'], ['name' => 'Stasiun Pengelasan', 'description' => 'Welding & assembly', 'capacity_per_hour' => 20, 'cost_per_hour' => 100000, 'is_active' => true]);
        WorkCenter::firstOrCreate(['code' => 'WC-GRND'], ['name' => 'Mesin Gerinda', 'description' => 'Finishing permukaan', 'capacity_per_hour' => 30, 'cost_per_hour' => 60000, 'is_active' => true]);
        WorkCenter::firstOrCreate(['code' => 'WC-COAT'], ['name' => 'Powder Coating Line', 'description' => 'Pengecatan powder coating', 'capacity_per_hour' => 40, 'cost_per_hour' => 80000, 'is_active' => true]);
    }
}
