<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ShippingLine;
use App\Models\User;
use App\Models\works\Worker;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Storage::deleteDirectory('sunat');

        // User::factory(10)->create();
        $this->call([
            IdentitySeeder::class,
            UserSeeder::class,
            DepartmentSeeder::class,
            ProvinceSeeder::class,
            DistrictSeeder::class,
            CategorySeeder::class,

            DocumentSeeder::class,
            OperationSeeder::class,
            IntegrationSeeder::class,

            CurrencySeeder::class,

            UnitSeeder::class,

            AffectationSeeder::class,

            DetractionSeeder::class,

            PerceptionSeeder::class,

            PaymentMethodSeeder::class,

            TypeCreditNoteSeeder::class,
            TypeDebitNoteSeeder::class,

            ReasonTransferSeeder::class,

            CompanySeeder::class,
            //ProductSeeder::class,

            //ClientSeeder::class,

            CountrySeeder::class,

            PhoneCodeSeeder::class,
            ClientSeeder::class,
            CustomBrokerSeeder::class,
            LocationSeeder::class,
            DamageSeeder::class,
            ComponentSeeder::class,
            MethodSeeder::class,

            PortSeeder::class,
            ContainerTypeSeeder::class,
            ReeferMachineSeeder::class,
            ShippingLineSeeder::class,
            VesselSeeder::class,
            ReeferTechnologySeeder::class

        ]);
        //trabajadores
        Worker::factory(5)->create();
    }
}
