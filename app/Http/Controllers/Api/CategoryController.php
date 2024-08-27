<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Mockery\Exception;

class CategoryController extends Controller
{
    //--------------------------------------------------------Api pour lister les catégories------------------------------------------------------------------
    public function index()
    {
        try {
            $category = Category::with('produit')->get();
            return $this->jsonResponse(true, 'Liste des catégories ', $category );

        }catch (Exception $exception){
            return $this->jsonResponse( false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //-------------------------------------------------------Api pour sauvegarder les catégories---------------------------------------------------------
    public function store(CategoryRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $category = Category::create($validatedData);

            return $this->jsonResponse(true, 'Catéorie créée avec succès !', $category, 201);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //---------------------------------------------------------Api pour avoir les détails d'une catégorie------------------------------------------------------
    public function show(string $id)
    {
        try {
            //$category = Category::findOrFail($id);
            $categoryToLoad = Category::with('produit')->findOrFail($id);
            return $this->jsonResponse(true, 'Catégorie : ', $categoryToLoad);
        }catch (\Exception $exception){
            return $this->jsonResponse( false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //-----------------------------------------------------------Api de modification des catégorie-------------------------------------------------------
    public function update(CategoryRequest $request, string $id)
    {
        try {
            $validatedData = $request->validated();

            $categoryToUpdate = Category::FindOrFail($id);

            $categoryToUpdate->update($validatedData);

            return $this->jsonResponse(true, 'Categorie modfiée avec succès !', $categoryToUpdate);
        }catch (\Exception $exception){
            return $this->jsonResponse( false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //-----------------------------------------------------------Api de suppression des catégories-------------------------------------------------------
    public function destroy(string $id)
    {
        try {
            $categoryToDelete = Category::FindOrFail($id);
            $categoryToDelete->delete();
            return $this->jsonResponse(true, 'Categorie supprimée avec succès !', $categoryToDelete);
        }catch (\Exception $exception){
            return $this->jsonResponse( false, 'Erreur !', $exception->getMessage(), 500);
        }
    }

    //-----------------------------------------------------------Api de recherche de catégory-------------------------------------------------------
    public function search(Request $request)
    {
        try {
            $categoryToSearch = Category::where('libelle', 'LIKE', '%'.$request->search.'%')->orderBy('id', 'desc')->paginate(10);
            if ($categoryToSearch->count() > 0){
                return $this->jsonResponse(true, 'Catégorie trouvé !! ', $categoryToSearch);
            }else{
                return $this->jsonResponse(false, 'Category non trouvé !', [], 403);
            }
        }catch (Exception $exception){
            return $this->jsonResponse( false, 'Erreur !', $exception->getMessage(), 500);
        }
    }
}
