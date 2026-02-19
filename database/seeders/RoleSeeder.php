<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role1 = Role::create(['name' => 'Super']);
        $role2 = Role::create(['name' => 'Admin']);
        $role3 = Role::create(['name' => 'Supervisor']);
        $role4 = Role::create(['name' => 'Asistente']);
        $role5 = Role::create(['name' => 'Inspector']);
        $role6 = Role::create(['name' => 'Reparador']);
        $role7 = Role::create(['name' => 'Tecnico']);
        $role8 = Role::create(['name' => 'Facturacion']);
        $role9 = Role::create(['name' => 'Contabilidad']);
        $role10 = Role::create(['name' => 'Depot']);

        Permission::create(['name' => 'companies'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'companies.edit'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'companies.update'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'companies.api-token'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'companies.destroy'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'branches'])->assignRole([$role1, $role2, $role3, $role9]);
        Permission::create(['name' => 'branches.choose'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'branches.series'])->assignRole([$role1, $role2, $role3, $role9]);
        Permission::create(['name' => 'branches.integrations'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'users'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'damages'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'damages.update'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'damages.delete'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'components'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'components.update'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'components.delete'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'methods'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'methods.update'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'methods.delete'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'container-types.index'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'reefer-technologies.index'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'reefer-conditions'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'locations'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'locations.update'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'locations.delete'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'ports'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'ports.create'])->assignRole([$role1, $role2, $role3, $role4,]);
        Permission::create(['name' => 'ports.update'])->assignRole([$role1, $role2, $role3, $role4,]);
        Permission::create(['name' => 'ports.delete'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'lines'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'lines.update'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'lines.delete'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'line.vessels'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'line.vessels.edit'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'line.vessels.delete'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'line.contecategories'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'line.contecategories.update'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'line.contecategories.delete'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'clients'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'clients.update'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'clients.delete'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'providers'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'providers.update'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'providers.delete'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'brokers'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'brokers.update'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'brokers.delete'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'transports'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'transports.update'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'transports.delete'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'transport.drivers'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'transport.vehicles'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.create'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.update'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.delete'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'vouchers.index'])->assignRole([$role1, $role2, $role8, $role9]);
        Permission::create(['name' => 'vouchers.invoice'])->assignRole([$role1, $role2, $role8, $role9]);
        Permission::create(['name' => 'vouchers.show'])->assignRole([$role1, $role2, $role8, $role9]);
        Permission::create(['name' => 'vouchers.invoice.create'])->assignRole([$role1, $role2, $role8, $role9]);
        Permission::create(['name' => 'vouchers.anulate'])->assignRole([$role1, $role2, $role8, $role9]);
        Permission::create(['name' => 'voideds'])->assignRole([$role1, $role2, $role8, $role9]);
        Permission::create(['name' => 'vouchers.note'])->assignRole([$role1, $role2, $role8, $role9]);
        Permission::create(['name' => 'vouchers.note.create'])->assignRole([$role1, $role2, $role8, $role9]);
        Permission::create(['name' => 'despatches.index'])->assignRole([$role1, $role2, $role3, $role4, $role8, $role9]);
        Permission::create(['name' => 'despatches.create'])->assignRole([$role1, $role2, $role3, $role4, $role8, $role9]);
        Permission::create(['name' => 'manage_work.eir'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'manage_work.index'])->assignRole([$role1, $role2, $role3, $role4, $role5, $role6]);
        Permission::create(['name' => 'manage_work.edit'])->assignRole([$role1, $role2, $role3, $role4, $role5, $role6]);
        Permission::create(['name' => 'manage_work.labors'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'manage_work.workers'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'manage_work.photos.delete'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'manage_work.status'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'workers.index'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'workers.store'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'dispatches.index'])->assignRole([$role1, $role2, $role3, $role4, $role7]);
        Permission::create(['name' => 'dispatches.create'])->assignRole([$role1, $role2, $role3, $role4, $role7]);
        Permission::create(['name' => 'dispatches.update'])->assignRole([$role1, $role2, $role3, $role4, $role7]);
        Permission::create(['name' => 'dispatches.delete'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'dispatches.photos.delete'])->assignRole([$role1, $role2]);
        Permission::create(['name' => 'containers.traces'])->assignRole([$role1, $role2, $role3, $role4, $role5, $role7]);
        Permission::create(['name' => 'inspections.eir.list'])->assignRole([$role1, $role2, $role3, $role4, $role5, $role10]);
        Permission::create(['name' => 'inspections.eir.create'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'inspections.eir.edit'])->assignRole([$role1, $role2, $role3, $role4, $role5, $role10]);
        Permission::create(['name' => 'inspections.eir.update'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'inspections.eir.delete'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'inspections.eir.create-detail'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'inspections.eir.delete-detail'])->assignRole([$role1, $role2, $role3, $role5]);
        Permission::create(['name' => 'inspections.eir.delete-photos'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'inspections.eir.close'])->assignRole([$role1, $role2, $role3, $role4, $role5]);
        Permission::create(['name' => 'inspections.eir.void'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'inspections.leak.list'])->assignRole([$role1, $role2, $role3, $role4, $role7]);
        Permission::create(['name' => 'inspections.leak.create'])->assignRole([$role1, $role2, $role3, $role4, $role7]);
        Permission::create(['name' => 'inspections.leak.edit'])->assignRole([$role1, $role2, $role3, $role4, $role7]);
        Permission::create(['name' => 'inspections.leak.update'])->assignRole([$role1, $role2, $role3, $role7]);
        Permission::create(['name' => 'inspections.leak.delete'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'discharges.index'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'discharges.upload'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'discharges.anulate'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'discharges.containers.anulate'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'discharges.containers.update'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'discharges.edit'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'devolution.index'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'devolution.upload'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'devolution.anulate'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'devolution.containers.anulate'])->assignRole([$role1, $role2, $role3]);
        Permission::create(['name' => 'devolution.containers.update'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'devolution.edit'])->assignRole([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'reports'])->assignRole([$role1, $role2, $role3, $role4, $role8, $role9]);
        Permission::create(['name' => 'cashes.index'])->assignRole([$role1, $role2, $role3, $role4, $role9]);
        Permission::create(['name' => 'cashes.void'])->assignRole([$role1, $role2, $role9]);
        Permission::create(['name' => 'cash.report'])->assignRole([$role1, $role2, $role3, $role4, $role9]);
        Permission::create(['name' => 'bills.index'])->assignRole([$role1, $role2, $role3, $role4, $role9]);
        Permission::create(['name' => 'deposits.index'])->assignRole([$role1, $role2, $role9]);
        Permission::create(['name' => 'roofEvidence.update'])->assignRole([$role1, $role2, $role3, $role5]);
        Permission::create(['name' => 'roofEvidence.photo.create'])->assignRole([$role1, $role2, $role3, $role5, $role6]);
        Permission::create(['name' => 'roofEvidence.photo.delete'])->assignRole([$role1, $role2, $role3, $role5]);
    }
}
