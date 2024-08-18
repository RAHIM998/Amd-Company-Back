<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProduitRequest;
use App\Models\Produit;
use App\Models\Category;
use Illuminate\Http\Request;
use Mockery\Exception;

class ProduitController extends Controller
{
    //-----------------------------------------------------------------Api d'affichage des produits----------------------------------------------------
    public function index()
    {
        try {
            $produits = Produit::with('category')->get();
            return $this->jsonResponse(true, 'Liste des produits', $produits);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, $exception->getMessage());
        }
    }

    //---------------------------------------------------------------Api de sauvegarde de produit----------------------------------------------------------
    public function store(ProduitRequest $request)
    {
        try {
            $validatedData = $request->validated();
            if ($request->hasFile('image')) {
                $image = $this->imageToBlob($request->file('image'));
                $validatedData['image'] = $image;
            }

            $product = Produit::create($validatedData);
            return $this->jsonResponse(true, 'Produit créé avec succès !', $product, 201);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 400);
        }
    }

    //--------------------------------------------------------------Api d'affichage des détails d'un produit------------------------------------------------
    public function show(string $id)
    {
        try {
            $produit = Produit::findOrFail($id);

            return $this->jsonResponse(true, 'Produit trouvé ', $produit);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur', $exception->getMessage(), 500);
        }

    }

    //---------------------------------------------------------------Api de modification d'un produit------------------------------------------------------
    public function update(ProduitRequest $request, string $id)
    {
        try {
            $validatedData = $request->validated();

            $productToUpdate = Produit::FindOrFail($id);

            $productToUpdate->update($validatedData);

            return $this->jsonResponse(true, 'produit modifié avec succès !', $productToUpdate);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //--------------------------------------------------------------Api de suppression de produits-------------------------------------------------------------
    public function destroy(string $id)
    {
        try {
            $productToDelete = Produit::FindOrFail($id);
            $productToDelete->delete();

            return $this->jsonResponse(true, 'Produit supprimé avec succès !', $productToDelete);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //---------------------------------------------------------------Api de recherche de produit------------------------------------------------------------
    public function search(Request $request)
    {
        try {
            $categoryToSearch = Produit::where('libelle', 'LIKE', '%'.$request->search.'%')->orderBy('id', 'desc')->get();
            if ($categoryToSearch->count() > 0){
                return $this->jsonResponse(true, 'Produits trouvé !! ', $categoryToSearch);
            }else{
                return $this->jsonResponse(false, 'Produit non trouvé !', [], 403);
            }
        }catch (Exception $exception){
            return $this->jsonResponse( false, 'Erreur !', $exception->getMessage(), 500);
        }
    }
}
