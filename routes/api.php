<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DespatchController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\PhoneCodeController;
use App\Http\Controllers\Api\SummaryController;
use App\Http\Controllers\Api\UbigeoController;
use App\Http\Controllers\Api\VoidedController;
use App\Models\Client;
use App\Models\Country;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])
    ->name('api.register');

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('refresh', [AuthController::class, 'refresh']);
Route::post('me', [AuthController::class, 'me']);

Route::apiResource('companies', CompanyController::class)
    ->middleware('auth:jwt');

Route::post('companies/{company}/token', [CompanyController::class, 'token'])
    ->middleware('auth:jwt');

Route::post('invoice/send', [InvoiceController::class, 'send'])
    ->middleware('auth:sanctum')
    ->name('api.invoice.send');

Route::post('invoice/xml', [InvoiceController::class, 'xml'])
    ->middleware('auth:sanctum')
    ->name('api.invoice.xml');

Route::post('invoice/pdf', [InvoiceController::class, 'pdf'])
    ->middleware('auth:sanctum')
    ->name('api.invoice.pdf');

Route::post('note/send', [NoteController::class, 'send'])
    ->middleware('auth:sanctum')
    ->name('api.note.send');

Route::post('note/xml', [NoteController::class, 'xml'])
    ->middleware('auth:sanctum')
    ->name('api.note.xml');

Route::post('note/pdf', [NoteController::class, 'pdf'])
    ->middleware('auth:sanctum')
    ->name('api.note.pdf');

// Despatch
Route::post('despatch/send', [DespatchController::class, 'send'])
    ->middleware('auth:sanctum')
    ->name('api.despatch.send');

Route::post('despatch/xml', [DespatchController::class, 'xml'])
    ->middleware('auth:sanctum')
    ->name('api.despatch.xml');

Route::post('despatch/pdf', [DespatchController::class, 'pdf'])
    ->middleware('auth:sanctum')
    ->name('api.despatch.pdf');

Route::post('summary/send', [SummaryController::class, 'send'])
    ->middleware('auth:sanctum')
    ->name('api.summary.send');

Route::post('summary/xml', [SummaryController::class, 'xml'])
    ->middleware('auth:sanctum')
    ->name('api.summary.xml');

Route::post('summary/pdf', [SummaryController::class, 'pdf'])
    ->middleware('auth:sanctum')
    ->name('api.summary.pdf');

Route::post('voided/send', [VoidedController::class, 'send'])
    ->middleware('auth:sanctum')
    ->name('api.voided.send');

Route::post('voided/xml', [VoidedController::class, 'xml'])
    ->middleware('auth:sanctum')
    ->name('api.voided.xml');

Route::post('voided/pdf', [VoidedController::class, 'pdf'])
    ->middleware('auth:sanctum')
    ->name('api.voided.pdf');

Route::post('ubigeos', [UbigeoController::class, 'index'])
    ->name('api.ubigeos.index');

Route::post('phone-codes', [PhoneCodeController::class, 'index'])
    ->name('api.phone-codes.index');


Route::post('clients', function (Request $request) {

    return Client::select('id', 'rznSocial as name', 'numDoc as description')
        ->when($request->search, function ($query, $search) {

            $query->where(function ($query) use ($search) {
                $query->where('rznSocial', 'like', '%' . $search . '%')
                    ->orWhere('numDoc', 'like', '%' . $search . '%');
            });
        })->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )->where('company_id', session('company')->id)
        ->get();
})->middleware('web')
    ->name('api.clients.index');

Route::post('products', function (Request $request) {

    return Product::select('id', 'codProducto', 'codBarras', 'descripcion as name')
        ->when($request->search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('descripcion', 'like', '%' . $search . '%')
                    ->orWhere('codProducto', 'like', '%' . $search . '%')
                    ->orWhere('codBarras', 'like', '%' . $search . '%');
            });
        })->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )
        ->where('unidad', 'ZZ')
        ->where('branch_id', session('branch')->id)
        ->get();
})->middleware('web')
    ->name('api.products.index');

Route::post('countries', function (Request $request) {

    return Country::select('id', 'description as name')
        ->when($request->search, function ($query, $search) {

            $query->where('description', 'like', '%' . $search . '%');

            /* $query->where(function ($query) use ($search) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                }); */
        })->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )->get();
})->name('api.countries.index');
