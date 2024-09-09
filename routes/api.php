<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


//Api qui necessite pas de connexion
Route::group(['middleware' => 'guest'], function () {
    Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
});


//Api Contact
Route::post('contact', [\App\Http\Controllers\Api\ContactController::class, 'store']);


//Api Catégory
Route::get('category', [CategoryController::class, 'index']);
Route::get('/search', [CategoryController::class, 'search']);
Route::get('/category/{id}', [CategoryController::class, 'show']);

//Api produit
Route::get('produit', [ProduitController::class, 'index']);
Route::get('produit/{id}', [ProduitController::class, 'show']);
Route::get('search', [ProduitController::class, 'search']);
Route::get('produit/{id}/products', [ProduitController::class, 'getProductsByCategory']);



//Api qui necessite de la connexion
Route::group(['middleware' => 'auth:api'], function () {
    //Api de déconnexion
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

    //Api user
    Route::get('user', [UserController::class, 'index']);
    Route::get('user/{id}', [UserController::class, 'show']);
    Route::put('user/{id}', [UserController::class, 'update']);

    //Api Catégories

    //Api Paiement
    Route::post('/paiement', [PaiementController::class, 'store']);


    //Api Commande
    Route::post('commande', [CommandeController::class, 'store']);
    Route::get('commande/{id}', [CommandeController::class, 'show']);
    Route::get('commande', [CommandeController::class, 'index']);
    Route::delete('commande/{id}', [CommandeController::class, 'destroy']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        //Api Users
        Route::post('/user', [UserController::class, 'store']);
        Route::delete('/user/{id}', [UserController::class, 'destroy']);

        //Api Catégories
        Route::post('/category', [CategoryController::class, 'store']);
        Route::put('/category/{id}', [CategoryController::class, 'update']);
        Route::delete('/category/{id}', [CategoryController::class, 'destroy']);

        //Api produit
        Route::post('/produit', [ProduitController::class, 'store']);
        Route::put('/produit/{id}', [ProduitController::class, 'update']);
        Route::delete('/produit/{id}', [ProduitController::class, 'destroy']);

        //Api Commande
        Route::get('/commande/encoursjours', [CommandeController::class, 'commandeEnCoursDuJour']);
        Route::get('/commande/annulee', [CommandeController::class, 'commandeAnnuleeDuJour']);
        Route::get('/commande/valide', [CommandeController::class, 'commandeValideeDuJour']);
        Route::get('/commande/jours', [CommandeController::class, 'commandeDuJours']);
        Route::get('/commande/encours', [CommandeController::class, 'CommandeEnCours']);
        Route::put('/commande/{id}', [CommandeController::class, 'update']);

        //Api Paiements
        Route::get('/paiement/jours', [PaiementController::class, 'paiementjournalier']);
        Route::get('/paiement/enAttente', [PaiementController::class, 'paiementEnAttentes']);
        Route::get('/paiement', [PaiementController::class, 'index']);
        Route::get('/paiement/{id}', [PaiementController::class, 'show']);
    });

});

