<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProduitRequest;
use App\Models\Produit;
use App\Models\Category;
use App\Models\ProduitImage;
use Illuminate\Http\Request;
use Mockery\Exception;

class ProduitController extends Controller
{
    //-----------------------------------------------------------------Api d'affichage des produits----------------------------------------------------
    public function index()
    {
        try {
            $produits = Produit::with('images')->get();
            return $this->jsonResponse(true, 'Liste des produits', $produits);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, $exception->getMessage());
        }
    }

    //-----------------------------------------------------------------Api d'affichage des produits d'une catégorie----------------------------------------------------
    public function getProductsByCategory($categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);

            // Récupérer les produits avec leurs images
            $produits = $category->produit()->with('images')->get();

            return $this->jsonResponse(true, 'Produits de la catégorie récupérés avec succès.', $produits);
        } catch (\Exception $exception) {
            return $this->jsonResponse(false, 'Erreur lors de la récupération des produits.', $exception->getMessage(), 500);
        }
    }


    //---------------------------------------------------------------Api de sauvegarde de produit----------------------------------------------------------
    public function store(ProduitRequest $request)
    {
        try {
            // Valider les données
            $validatedData = $request->validated();

            // Créer le produit en premier pour obtenir l'ID
            $product = Produit::create($validatedData);

            // Gérer les images
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $image) {
                    // Utiliser la méthode imageToBlob pour convertir l'image en base64
                    $base64Image = $this->imageToBlob($image);

                    // Sauvegarder l'image en base64 dans la base de données
                    ProduitImage::create([
                        'produit_id' => $product->id, // Utiliser l'ID du produit créé
                        'image' => $base64Image
                    ]);
                }
            }

            // Retourner une réponse
            return $this->jsonResponse(true, 'Produit créé avec succès !', $product, 201);
        } catch (\Exception $exception) {
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 400);
        }
    }


    //--------------------------------------------------------------Api d'affichage des détails d'un produit------------------------------------------------
    public function show(string $id)
    {
        try {
            $produit = Produit::with('images')->findOrFail($id);


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
