<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,DashboardController,ClienteController,CargasController,PropuestaController,ReporteController,AdminController,SeguimientoController};

Route::get('/', fn()=>redirect('/inicio'));
Route::middleware('guest')->group(function (): void {
    Route::get('/login',[AuthController::class,'showLogin'])->name('login');
    Route::post('/login',[AuthController::class,'login'])->name('login.store');
});
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::middleware(['auth','active'])->group(function (): void {
    Route::get('/inicio',[DashboardController::class,'index'])->name('dashboard');
    Route::get('/clientes',[ClienteController::class,'show'])->name('clientes.show');
    Route::get('/api/clientes/sugerencias',[ClienteController::class,'suggestions'])->name('clientes.suggestions');
    Route::get('/api/clientes',[ClienteController::class,'search'])->name('clientes.search');
    Route::post('/clientes/generar-word',[ClienteController::class,'generateWord'])->name('clientes.word');
    Route::get('/cargas',[CargasController::class,'index'])->middleware('admin')->name('cargas.index');
    Route::get('/cargas/asignacion',[CargasController::class,'asignacion'])->middleware('admin')->name('cargas.asignacion');
    Route::get('/cargas/pagos',[CargasController::class,'pagos'])->middleware('admin')->name('cargas.pagos');
    Route::get('/cargas/ccd',[CargasController::class,'pending'])->middleware('admin')->defaults('kind', 'ccd')->name('cargas.ccd');
    Route::get('/cargas/data',[CargasController::class,'pending'])->middleware('admin')->defaults('kind', 'data')->name('cargas.data.pending');
    Route::get('/cargas/plantilla',[CargasController::class,'template'])->name('cargas.plantilla');
    Route::post('/cargas/data',[CargasController::class,'storeData'])->middleware('admin')->name('cargas.data');
    Route::post('/cargas/pagos',[CargasController::class,'storePagos'])->middleware('admin')->name('cargas.pagos.store');
    Route::post('/cargas/asignacion',[CargasController::class,'storeImport'])->middleware('admin')->defaults('type', 'asignacion')->name('cargas.asignacion.store');
    Route::get('/cargas/importaciones/{importacion}',[CargasController::class,'showImport'])->name('cargas.importaciones.show');
    Route::get('/cargas/importaciones/{importacion}/estado',[CargasController::class,'status'])->name('cargas.importaciones.estado');
    Route::get('/cargas/importaciones/{importacion}/errores',[CargasController::class,'errors'])->name('cargas.importaciones.errores');
    Route::post('/cargas/seguimientos',[CargasController::class,'storeSeguimientos'])->name('cargas.seguimientos');
    Route::post('/cargas/evolutivo',[CargasController::class,'storeEvolutivo'])->name('cargas.evolutivo');
    Route::get('/reportes',[ReporteController::class,'index'])->name('reportes.index');
    Route::get('/reportes/descargar',[ReporteController::class,'descargarDocumento'])->name('reportes.descargar');
    Route::post('/reportes/eliminar',[ReporteController::class,'eliminarDocumento'])->name('reportes.eliminar');
    Route::get('/propuestas',[PropuestaController::class,'form'])->name('propuestas.form');
    Route::post('/propuestas',[PropuestaController::class,'store'])->name('propuestas.store');
    Route::get('/propuestas/excel',[PropuestaController::class,'excel'])->name('propuestas.excel');
    Route::post('/propuestas/eliminar',[PropuestaController::class,'destroy'])->name('propuestas.destroy');
    Route::middleware('admin')->group(function (): void {
        Route::get('/admin',[AdminController::class,'index'])->name('admin.index');
        Route::post('/admin/crear',[AdminController::class,'store'])->name('admin.store');
        Route::post('/admin/actualizar',[AdminController::class,'update'])->name('admin.update');
        Route::post('/admin/toggle',[AdminController::class,'toggle'])->name('admin.toggle');
        Route::post('/admin/eliminar',[AdminController::class,'destroy'])->name('admin.destroy');
    });
    Route::get('/seguimientos',[SeguimientoController::class,'index'])->name('seguimientos.index');
    Route::get('/api/seguimientos',[SeguimientoController::class,'list'])->name('seguimientos.list');
    Route::post('/api/seguimientos',[SeguimientoController::class,'store'])->name('seguimientos.store');
    Route::post('/api/seguimientos/eliminar',[SeguimientoController::class,'destroy'])->name('seguimientos.destroy');
    Route::get('/api/evolutivo',[SeguimientoController::class,'evolutivo'])->name('seguimientos.evolutivo');
});
// APIs de sesión permanecen en el grupo web para autenticación, sesión y CSRF nativos.
// /vista era un debug que apuntaba a un archivo ausente; no se publica.
