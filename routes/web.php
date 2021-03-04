<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Rutas de Prueba
Route::get('/', function () {
    return view('welcome');
});

Route::get('/pruebas/{nombre?}', function ($nombre = null) {
    $texto = '<h2> Texto de ruta pruebas </h2>';
    $texto .= "Nombre: " . $nombre;
    return view('pruebas', array(
        'texto' => $texto
    ));
});

Route::get('/test-orm', 'PruebaController@testORM');

//Rutas de la API
/*Rutas de Prueba
    Route::get('/user/pruebas', 'UserController@pruebas');
    Route::get('/category/pruebas', 'CategoryController@pruebas');
    Route::get('/post/pruebas', 'PostController@pruebas');
    */
//Rutas de Controlador Usuario
Route::get('/api/test', 'UserController@pruebaTecnica');
Route::post('/api/register', 'UserController@register');
Route::post('/api/login', 'UserController@login');
Route::put('/api/update', 'UserController@update');
Route::post('/api/upload', 'UserController@upload')->middleware(\App\Http\Middleware\ApiAuthMiddleware::class);
Route::get('/api/avatar/{filename}', 'UserController@getImage');
Route::get('/api/detail/{id}', 'UserController@detail');

//Rutas de Controlador Categoria
Route::resource('/api/category', 'CategoryController');

//Rutas de Controlador Post
Route::resource('/api/post', 'PostController');
Route::post('/api/post/upload', 'PostController@upload');
Route::get('/api/post/image/{filename}', 'PostController@getImage');
Route::get('/api/post/category/{id}', 'PostController@getPostsByCategory');
Route::get('/api/post/user/{id}', 'PostController@getPostsByUser');
