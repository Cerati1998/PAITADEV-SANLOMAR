<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\ContainerOperationalTraceController;
use App\Http\Controllers\ContainerTypeController;
use App\Http\Controllers\CustomBrokerController;
use App\Http\Controllers\DamageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DespatchController;
use App\Http\Controllers\DevolutionController;
use App\Http\Controllers\DischargueController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\operations\DispatchController;
use App\Http\Controllers\operations\InspectionController;
use App\Http\Controllers\operations\WorkOrderController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ReeferConditionController;
use App\Http\Controllers\ReeferTechnologyController;
use App\Http\Controllers\ShippingLineController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoidedController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WorkerController;
use App\Http\Middleware\CheckCompanySelected;
use App\Models\ContainerOperationalTrace;
use App\Models\Invoice;
use App\Models\ReeferCondition;
use App\Models\Vessel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Str;

/* Route::get('/', function () {
    return view('welcome');
}); */

Route::redirect('/', '/dashboard');

Route::middleware([
    'auth',
])->group(function () {

    Route::middleware([
        CheckCompanySelected::class
    ])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index',])->name('dashboard');
        //Ventas
        Route::get('vouchers', [VoucherController::class, 'index',])->name('vouchers.index');
        Route::get('vouchers/invoice', [VoucherController::class, 'invoice',])->name('vouchers.invoice');
        Route::get('vouchers/note', [VoucherController::class, 'note',])->name('vouchers.note');
        Route::get('vouchers/{invoice}', [VoucherController::class, 'show',])->name('vouchers.show');


        Route::get('voideds', [VoidedController::class, 'index',])->name('voideds.index');

        Route::get('despatchs', [DespatchController::class, 'index',])->name('despatchs.index');
        Route::get('despatchs/create', [DespatchController::class, 'create',])->name('despatchs.create');

        Route::resource('clients', ClientController::class);
        Route::resource('providers', ProviderController::class);
        Route::resource('brokers', CustomBrokerController::class);
        Route::resource('transports', TransportController::class)->names('transports');
        Route::get('transport/{transport}/drivers', [TransportController::class, 'drivers'])->name('transport.drivers');
        Route::get('transport/{transport}/vehicles', [TransportController::class, 'vehicles'])->name('transport.vehicles');

        //Inventario
        Route::get('products', [ProductController::class, 'index',])->name('products.index');

        Route::get('companies/edit', [CompanyController::class, 'edit',])->name('companies.edit');
        Route::put('companies/update', [CompanyController::class, 'update',])->name('companies.update');
        Route::get('companies/api-token', [CompanyController::class, 'apiToken',])->name('companies.api-token');
        Route::delete('companies', [CompanyController::class, 'destroy',])->name('companies.destroy');

        Route::resource('branches', BranchController::class)
            ->except('update');
        Route::get('branches-choose', [BranchController::class, 'choose',])->name('branches.choose');
        Route::get('branches/{branch}/series', [BranchController::class, 'series',])->name('branches.series');
        Route::get('branches/{branch}/integrations', [BranchController::class, 'integrations',])->name('branches.integrations');

        Route::resource('users', UserController::class);

        //maestras para inspecciones
        Route::resource('damages', DamageController::class)->names('damages')->except('show');
        Route::resource('components', ComponentController::class)->names('components')->except('show');
        Route::resource('methods', MethodController::class)->names('methods')->except('show');
        Route::resource('locations', LocationController::class)->names('locations')->except('show');

        //maestras para admisión
        Route::resource('lines', ShippingLineController::class)->names('lines')->except('show');
        Route::prefix('line')->group(function () {
            Route::get('/{line}/vessels', [ShippingLineController::class, 'vessels'])->name('line.vessels');
            Route::get('/{line}/container-categories', [ShippingLineController::class, 'containerCategories'])->name('line.contecategories');
        });
        Route::resource('ports', PortController::class)->names('ports')->except('show');
        Route::resource('container-types', ContainerTypeController::class)->names('container-types')->except('show');
        Route::resource('reefer-technologies', ReeferTechnologyController::class)->names('reefer-technologies')->except('show');
        Route::resource('reefer-conditions', ReeferConditionController::class)->names('reefer-conditions')->except('show');

        //rutas de carga de data de contenedores
        Route::resource('discharges', DischargueController::class)->names('discharges')->except('show');
        Route::get('discharges/{dischargue}/containers', [DischargueController::class, 'dischargue_containers'])->name('discharge.containers');
        Route::resource('devolutions', DevolutionController::class)->names('devolutions')->except('show');
        Route::get('devolutions/{devolution}/containers', [DevolutionController::class, 'devolution_containers'])->name('devolution.containers');
        Route::resource('containers', ContainerController::class)->names('containers');
        Route::get('downloads/dischargue-template', function () {
            return response()->download(resource_path('templates/massive_dischargue.xlsx'));
        })->name('dischargue-template');
        Route::get('downloads/devolution-template', function () {
            return response()->download(resource_path('templates/massive_devolution.xlsx'));
        })->name('devolution-template');

        //ruta de listado de status de contenedores e inspecciones
        Route::get('containers-traces', [ContainerOperationalTraceController::class, 'index'])->name('containers.traces');

        //grupo de rutas para inspecciones
        Route::prefix('inspections')->group(function () {
            Route::get('pti', [InspectionController::class, 'manage_pti'])
                ->name('inspections.pti.list');

            Route::get('eir', [InspectionController::class, 'manage_eir'])
                ->name('inspections.eir.list');

            Route::get('leak-test', [InspectionController::class, 'manage_leak'])
                ->name('inspections.leak.list');

            Route::get('{trace?}/create-pti', [InspectionController::class, 'create_pti'])
                ->name('inspections.pti.create')
                ->where('trace', '.*');

            Route::get('{trace?}/create-eir', [InspectionController::class, 'create_eir'])
                ->name('inspections.eir.create')
                ->where('trace', '.*');

            Route::get('{trace?}/create-leak', [InspectionController::class, 'manage_leak'])
                ->name('inspections.leak.create')
                ->where('trace', '.*');

            // para editar inspecciones
            Route::get('{pti}/edit-pti', [InspectionController::class, 'edit_pti'])
                ->name('inspections.pti.edit');

            Route::get('{eir}/edit-eir', [InspectionController::class, 'edit_eir'])
                ->name('inspections.eir.edit');

            Route::get('{leak}/edit-leak', [InspectionController::class, 'edit_leak'])
                ->name('inspections.leak.edit');

            //generar formato pdf
            Route::get('{eir}/generate-pdf', [InspectionController::class, 'generate_eir_pdf'])
                ->name('inspections.eir.pdf');
        });

        Route::post('/eir/upload-header-photo', [InspectionController::class, 'uploadHeaderPhoto'])
            ->name('eir.upload-header-photo');

        Route::post('/eir/upload-detail-photo', [InspectionController::class, 'uploadDetailPhoto'])
            ->name('eir.upload-detail-photo');

        //seccion de trabajos
        Route::get('workers', [WorkerController::class, 'index'])->name('workers.index');
        Route::post('add-worker', [WorkerController::class, 'store'])->name('workers.store');
        Route::prefix('work-orders')->group(function () {
            //ordenes de trabajo para estructura
            Route::get('box', [WorkOrderController::class, 'manage_box'])
                ->name('work_orders.box');
            Route::get('box/create', [WorkOrderController::class, 'create_box'])
                ->name('work_orders.box.create');

            //ordenes de trabajo para maquina
            Route::get('mach', [WorkOrderController::class, 'manage_mach'])
                ->name('work_orders.mach');

            Route::post('upload-photos', [WorkOrderController::class, 'upload_labor_photos'])
                ->name('work_orders.upload');
        });


        Route::get('dispatches', [DispatchController::class, 'manage_dispatches'])->name('dispatches.index');


        //endpoint para selectores
        Route::get('searchLine', [ShippingLineController::class, 'searchLines'])->name('line.search');
        Route::get('searchVessel', [ShippingLineController::class, 'searchVessels'])->name('vessel.search');
        Route::get('searchPort', [PortController::class, 'searchPorts'])->name('port.search');
        Route::post('searchDocument', [UserController::class, 'searchDocument'])->name('user.searchDocument');
        Route::get('searchClient', [ClientController::class, 'searchClients'])->name('client.search');
        Route::get('searchBroker', [CustomBrokerController::class, 'searchBrokers'])->name('broker.search');
        Route::get('searchContainer', [ContainerController::class, 'getContainers'])->name('container.search');
        Route::get('searchTrace', [ContainerOperationalTraceController::class, 'getContainers'])->name('trace.search');
        Route::get('searchWorkers', [WorkerController::class, 'searchWorkers'])->name('worker.search');

        Route::get('searchLocation', [LocationController::class, 'getLocations'])->name('location.search');
        Route::get('searchDamage', [DamageController::class, 'getDamages'])->name('damage.search');
        Route::get('searchComponent', [ComponentController::class, 'getComponents'])->name('component.search');
        Route::get('searchMethod', [MethodController::class, 'getMethods'])->name('method.search');
    });
});

Route::resource('companies', CompanyController::class)
    ->only(['index', 'create', 'show']);


/* Route::get('documentacion', function () {
    return view('docs');
}); */
