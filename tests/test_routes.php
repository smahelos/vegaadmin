<?php

use App\Http\Controllers\Admin\ClientCrudController;
use App\Http\Controllers\Admin\SupplierCrudController;
use App\Http\Controllers\Admin\ProductCrudController;
use App\Http\Controllers\Admin\InvoiceCrudController;
use App\Http\Requests\Admin\ClientRequest;
use App\Http\Requests\Admin\SupplierRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Requests\Admin\InvoiceRequest;

// Test routes for CRUD operations (only in testing environment)
if (app()->environment('testing')) {
    Route::post('/admin/client', function (ClientRequest $request) {
        $controller = new ClientCrudController();
        $controller->setup();
        return $controller->store();
    })->middleware('web');

    Route::post('/admin/supplier', function (SupplierRequest $request) {
        $controller = new SupplierCrudController();
        $controller->setup();
        return $controller->store();
    })->middleware('web');

    Route::post('/admin/product', function (ProductRequest $request) {
        try {
            $controller = app(ProductCrudController::class);
            $controller->setup();
            $result = $controller->store();
            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware('web');

    Route::post('/admin/invoice', function (InvoiceRequest $request) {
        $controller = new InvoiceCrudController();
        $controller->setup();
        return $controller->store();
    })->middleware('web');
}
