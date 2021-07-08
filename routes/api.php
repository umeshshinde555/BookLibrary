<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', 'Api\UserController@register');
Route::post('login', 'Api\UserController@login');
  
Route::group(['middleware' => ['jwt.verify']], function() {
     Route::get('get-user', 'Api\UserController@getAuthenticatedUser');
     Route::get('books', 'Api\BookController@index');
     Route::get('books/{id}', 'Api\BookController@show');
     Route::post('books', 'Api\BookController@store');
     Route::post('books/{id}', 'Api\BookController@update');
     Route::delete('books/{id}', 'Api\BookController@destroy');

     Route::post('rent-book', 'Api\BookController@rentBook');
     Route::post('return-book', 'Api\BookController@returnBook');
     
     Route::get('rented-books', 'Api\BookController@getRentedBooks');
});
