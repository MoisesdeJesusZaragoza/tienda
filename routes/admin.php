<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\PedidoController;

Route::get('', [HomeController::class, 'index'])->name('admin.index');

// Categorias
Route::resource('categorias', CategoriaController::class);
Route::group(['prefix' => 'categoria'], function () {
    Route::get('listado', [CategoriaController::class, 'listado'])->name('categoria.listado');
});

// Productos
Route::resource('productos', ProductoController::class);
Route::group(['prefix' => 'producto'], function () {
    Route::get('listado', [ProductoController::class, 'listado'])->name('producto.listado');
});

//Pedidos
Route::resource('pedidos', PedidoController::class);
Route::group(['prefix' => 'pedido'], function () {
    Route::get('listado', [PedidoController::class, 'listado'])->name('pedido.listado');
    Route::get('envio/{id}', [PedidoController::class, 'envio'])->name('pedido.envio');
    Route::put('envio/{id}', [PedidoController::class, 'envioUpdate'])->name('pedido.envioUpdate');
    Route::get('correos/{id}', [PedidoController::class, 'correos'])->name('pedido.correos');
    Route::post('enviar/{id}', [PedidoController::class, 'enviar'])->name('pedido.enviar');
    Route::get('pdf/{id}', [PedidoController::class, 'pdf'])->name('pedido.pdf');
});
