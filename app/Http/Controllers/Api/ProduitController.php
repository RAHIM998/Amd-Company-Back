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
            $produits = Produit::with('category', 'images')->get();
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

            $product = Produit::create($validatedData);

            // Gérer les images
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $image) {
                    if ($image->isValid()) {
                        $base64Image = $this->imageToBlob($image);

                        if ($base64Image) {
                            ProduitImage::create([
                                'produit_id' => $product->id,
                                'image' => $base64Image
                            ]);
                        }
                    }
                }
            } else {
                return response()->json(['error' => 'Aucune image trouvée dans la requête'], 400);
            }
            return $this->jsonResponse(true, 'Produits de la catégorie récupérés avec succès.', $product, 201);
        } catch (\Exception $exception) {
            return $this->jsonResponse(false, 'Error', $exception->getMessage(), 500);
        }
    }


    //--------------------------------------------------------------Api d'affichage des détails d'un produit------------------------------------------------
    public function show(string $id)
    {
        try {
            $produit = Produit::with('images', 'category')->findOrFail($id);


            return $this->jsonResponse(true, 'Produit trouvé ', $produit);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur', $exception->getMessage(), 500);
        }

    }

    //---------------------------------------------------------------Api de modification d'un produit------------------------------------------------------
    public function update(Request $request, string $id)
    {

        try {
            // Trouver le produit à mettre à jour
            $productToUpdate = Produit::findOrFail($id);

            // Valider les données
            $validatedData = $request->validate([
                'category_id' => 'required|integer|exists:categories,id',
                'libelle' => 'required|string|max:255',
                'prix' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'description' => 'nullable|string|max:1000',
            ]);

            // Mettre à jour les champs individuellement
            $productToUpdate->category_id = $validatedData['category_id'];
            $productToUpdate->libelle = $validatedData['libelle'];
            $productToUpdate->prix = $validatedData['prix'];
            $productToUpdate->stock = $validatedData['stock'];
            $productToUpdate->description = $validatedData['description'];

            // Gérer les images
            if ($request->hasFile('image')) {
                $images = $request->file('image');
                $imagePaths = [];

                foreach ($images as $image) {
                    // Gérer le stockage de chaque image
                    $path = $image->store('images', 'public');
                    $imagePaths[] = $path;
                }

                // Mettre à jour le champ image avec les chemins des images
                $productToUpdate->image = json_encode($imagePaths); // Exemple : stocker les chemins comme JSON
            }

            // Sauvegarder les modifications
            $productToUpdate->save();

            // Répondre avec succès
            return $this->jsonResponse(true, 'Produit modifié avec succès !', $productToUpdate);
        } catch (\Exception $exception) {
            // Répondre en cas d'erreur
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
