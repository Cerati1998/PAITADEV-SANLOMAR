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
use App\Http\Controllers\ReportController;
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

Route::redirect('/', '/dashboard');

Route::middleware([
    'auth',
])->group(function () {

    Route::middleware([
        CheckCompanySelected::class
    ])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        //Ventas
        Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index')->middleware('can:vouchers.index');
        Route::get('vouchers/invoice', [VoucherController::class, 'invoice'])->name('vouchers.invoice')->middleware('can:vouchers.invoice');
        Route::get('vouchers/note', [VoucherController::class, 'note'])->name('vouchers.note')->middleware('can:vouchers.note');
        Route::get('vouchers/{invoice}', [VoucherController::class, 'show'])->name('vouchers.show')->middleware('can:vouchers.show');

        Route::get('voideds', [VoidedController::class, 'index'])->name('voideds.index')->middleware('can:voideds');

        Route::get('despatchs', [DespatchController::class, 'index'])->name('despatchs.index')->middleware('can:despatches.index');
        Route::get('despatchs/create', [DespatchController::class, 'create'])->name('despatchs.create')->middleware('can:despatches.create');

        Route::resource('clients', ClientController::class)->middleware('can:clients');
        Route::resource('providers', ProviderController::class)->middleware('can:providers');
        Route::resource('brokers', CustomBrokerController::class)->middleware('can:brokers');
        Route::resource('transports', TransportController::class)->names('transports')->middleware('can:transports');
        Route::get('transport/{transport}/drivers', [TransportController::class, 'drivers'])->name('transport.drivers')->middleware('can:transport.drivers');
        Route::get('transport/{transport}/vehicles', [TransportController::class, 'vehicles'])->name('transport.vehicles')->middleware('can:transport.vehicles');

        //Inventario
        Route::get('products', [ProductController::class, 'index'])->name('products.index')->middleware('can:products');

        Route::get('companies/edit', [CompanyController::class, 'edit'])->name('companies.edit')->middleware('can:companies.edit');
        Route::put('companies/update', [CompanyController::class, 'update'])->name('companies.update')->middleware('can:companies.update');
        Route::get('companies/api-token', [CompanyController::class, 'apiToken'])->name('companies.api-token')->middleware('can:companies.api-token');
        Route::delete('companies', [CompanyController::class, 'destroy'])->name('companies.destroy')->middleware('can:companies.destroyed');

        Route::resource('branches', BranchController::class)
            ->except('update')->middleware('can:branches');
        Route::get('branches-choose', [BranchController::class, 'choose'])->name('branches.choose')->middleware('can:branches.choose');
        Route::get('branches/{branch}/series', [BranchController::class, 'series'])->name('branches.series')->middleware('can:branches.series');
        Route::get('branches/{branch}/integrations', [BranchController::class, 'integrations'])->name('branches.integrations')->middleware('can:branches.integrations');

        Route::resource('users', UserController::class)->middleware('can:users');

        //maestras para inspecciones
        Route::resource('damages', DamageController::class)->names('damages')->except('show')->middleware('can:damages');
        Route::resource('components', ComponentController::class)->names('components')->except('show')->middleware('can:components');
        Route::resource('methods', MethodController::class)->names('methods')->except('show')->middleware('can:methods');
        Route::resource('locations', LocationController::class)->names('locations')->except('show')->middleware('can:locations');

        //maestras para admisión
        Route::resource('lines', ShippingLineController::class)->names('lines')->except('show')->middleware('can:lines');
        Route::prefix('line')->group(function () {
            Route::get('/{line}/vessels', [ShippingLineController::class, 'vessels'])->name('line.vessels')->middleware('can:line.vessels');
            Route::get('/{line}/container-categories', [ShippingLineController::class, 'containerCategories'])->name('line.contecategories')->middleware('can:line.contecategories');
        });
        Route::resource('ports', PortController::class)->names('ports')->except('show')->middleware('can:ports');
        Route::resource('container-types', ContainerTypeController::class)->names('container-types')->except('show')->middleware('can:container-types.index');
        Route::resource('reefer-technologies', ReeferTechnologyController::class)->names('reefer-technologies')->except('show')->middleware('can:reefer-technologies.index');
        Route::resource('reefer-conditions', ReeferConditionController::class)->names('reefer-conditions')->except('show')->middleware('can:reefer-conditions');

        //rutas de carga de data de contenedores
        Route::resource('discharges', DischargueController::class)->names('discharges')->except('show')->middleware('can:discharges.index');
        Route::get('discharges/{dischargue}/containers', [DischargueController::class, 'dischargue_containers'])->name('discharge.containers')->middleware('can:discharges.index');
        Route::resource('devolutions', DevolutionController::class)->names('devolutions')->except('show')->middleware('can:devolution.index');
        Route::get('devolutions/{devolution}/containers', [DevolutionController::class, 'devolution_containers'])->name('devolution.containers')->middleware('can:devolution.index');
        Route::resource('containers', ContainerController::class)->names('containers')->middleware('can:');
        Route::get('downloads/dischargue-template', function () {
            return response()->download(resource_path('templates/massive_dischargue.xlsx'));
        })->name('dischargue-template');
        Route::get('downloads/devolution-template', function () {
            return response()->download(resource_path('templates/massive_devolution.xlsx'));
        })->name('devolution-template');

        //ruta de listado de status de contenedores e inspecciones
        Route::get('containers-traces', [ContainerOperationalTraceController::class, 'index'])->name('containers.traces')->middleware('can:containers.traces');

        //grupo de rutas para inspecciones
        Route::prefix('inspections')->group(function () {
            Route::get('pti', [InspectionController::class, 'manage_pti'])
                ->name('inspections.pti.list');

            Route::get('eir', [InspectionController::class, 'manage_eir'])
                ->name('inspections.eir.list')->middleware('can:inspections.eir.list');

            Route::get('leak-test', [InspectionController::class, 'manage_leak'])
                ->name('inspections.leak.list')->middleware('can:inspections.leak.list');

            Route::get('{trace?}/create-pti', [InspectionController::class, 'create_pti'])
                ->name('inspections.pti.create')
                ->where('trace', '.*');

            Route::get('{trace?}/create-eir', [InspectionController::class, 'create_eir'])
                ->name('inspections.eir.create')
                ->where('trace', '.*')->middleware('can:inspections.eir.create');

            Route::get('{trace?}/create-leak', [InspectionController::class, 'manage_leak'])
                ->name('inspections.leak.create')
                ->where('trace', '.*')->middleware('can:inspections.leak.create');

            // para editar inspecciones
            Route::get('{pti}/edit-pti', [InspectionController::class, 'edit_pti'])
                ->name('inspections.pti.edit');

            Route::get('{eir}/edit-eir', [InspectionController::class, 'edit_eir'])
                ->name('inspections.eir.edit')->middleware('can:inspections.eir.edit');

            Route::get('{leak}/edit-leak', [InspectionController::class, 'edit_leak'])
                ->name('inspections.leak.edit')->middleware('can:inspections.leak.edit');

            //generar formato pdf
            Route::get('{eir}/generate-pdf', [InspectionController::class, 'generate_eir_pdf'])
                ->name('inspections.eir.pdf')->middleware('can:inspections.eir.list');
        });

        Route::post('/eir/upload-header-photo', [InspectionController::class, 'uploadHeaderPhoto'])
            ->name('eir.upload-header-photo');

        Route::post('/eir/upload-detail-photo', [InspectionController::class, 'uploadDetailPhoto'])
            ->name('eir.upload-detail-photo');

        //seccion de trabajos
        Route::get('workers', [WorkerController::class, 'index'])->name('workers.index')->middleware('can:workers.index');
        Route::post('add-worker', [WorkerController::class, 'store'])->name('workers.store')->middleware('can:workers.store');
        Route::prefix('work-orders')->group(function () {
            //ordenes de trabajo para estructura
            Route::get('box', [WorkOrderController::class, 'manage_box'])
                ->name('work_orders.box')->middleware('can:manage_work.index');
            Route::get('box/create', [WorkOrderController::class, 'create_box'])
                ->name('work_orders.box.create')->middleware('can:manage_work.index');

            //ordenes de trabajo para maquina
            Route::get('mach', [WorkOrderController::class, 'manage_mach'])
                ->name('work_orders.mach');

            Route::post('upload-photos', [WorkOrderController::class, 'upload_labor_photos'])
                ->name('work_orders.upload');
        });

        Route::get('dispatches', [DispatchController::class, 'manage_dispatches'])->name('dispatches.index')->middleware('can:dispatches.index');

        //endpoint para reportes
        Route::get('reports',[ReportController::class,'index'])->name('reports.index')->middleware('can:reports');
        Route::post('reports/dispatches',[DispatchController::class,'exportDispatches'])->name('report.dispatches');
        Route::post('reports/dispatches',[DispatchController::class,'exportDispatches'])->name('report.dispatches');

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
    ->only(['index', 'create', 'show'])->middleware('can:companies');


/* Route::get('documentacion', function () {
    return view('docs');
}); */
