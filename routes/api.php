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


//Api qui necessite de la connexion
Route::group(['middleware' => 'auth:api'], function () {
    //Api de déconnexion
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

    //Api Users
    Route::post('user', [UserController::class, 'store']);
    Route::get('user', [UserController::class, 'index']);
    Route::get('user/{id}', [UserController::class, 'show']);
    Route::put('user/{id}', [UserController::class, 'update']);
    Route::delete('user/{id}', [UserController::class, 'destroy']);

    //Api Catégories
    Route::get('category', [CategoryController::class, 'index']);
    Route::post('category', [CategoryController::class, 'store']);
    Route::get('category/{id}', [CategoryController::class, 'show']);
    Route::put('category/{id}', [CategoryController::class, 'update']);
    Route::delete('category/{id}', [CategoryController::class, 'destroy']);
    Route::get('search', [CategoryController::class, 'search']);

    //Api produit
    Route::get('produit', [ProduitController::class, 'index']);
    Route::post('produit', [ProduitController::class, 'store']);
    Route::get('produit/{id}', [ProduitController::class, 'show']);
    Route::put('produit/{id}', [ProduitController::class, 'update']);
    Route::delete('produit/{id}', [ProduitController::class, 'destroy']);
    Route::get('search', [ProduitController::class, 'search']);

    //Api Commande
    Route::get('commande/encours', [CommandeController::class, 'commandeEnCoursDuJour']);
    Route::get('commande/annulee', [CommandeController::class, 'commandeAnnuleeDuJour']);
    Route::get('commande/valide', [CommandeController::class, 'commandeValideeDuJour']);
    Route::get('commande', [CommandeController::class, 'index']);
    Route::post('commande', [CommandeController::class, 'store']);
    Route::get('commande/{id}', [CommandeController::class, 'show']);
    Route::put('commande/{id}', [CommandeController::class, 'update']);


    //Api Paiements
    Route::get('/paiement/jours', [PaiementController::class, 'paiementjournalier']);
    Route::get('/paiement/enAttente', [PaiementController::class, 'paiementEnAttentes']);
    Route::get('paiement', [PaiementController::class, 'index']);
    Route::post('paiement', [PaiementController::class, 'store']);
    Route::get('paiement/{id}', [PaiementController::class, 'show']);


});

