<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Mockery\Exception;


class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::all();
            return $this->jsonResponse(true, 'Liste des utilisateurs', $users);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }
    }


    //---------------------------------------------------------------------Api de sauvegarde-----------------------------------------------------------
    public function store(UserRequest $request)
    {
        try {
            $validatedData = $request->validated();

            if ($request->hasFile('image')) {
                $image = $this->imageToBlob($request->file('image'));
                $validatedData['image'] = $image;
            }else {
                $validatedData['image'] = null;
            }

            $users = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'telephone' => $validatedData['telephone'],
                'image' => $validatedData['image'],
                'password' => Hash::make($validatedData['password']),
                'role' => "admin",
            ]);



            return $this->jsonResponse(true, "Utilisateur créé avec succès !", $users, 201);

        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erruer !', $exception->getMessage(), 500);
        }
    }

    //-------------------------------------------------------------------Api details des users------------------------------------------------------------
    public function show(string $id)
    {
        try {
            $user = User::findOrFail($id);
            return $this->jsonResponse(true, "Détails de l'utilisateur", $user, 200);

        } catch (Exception $exception) {
            return $this->jsonResponse(false, $exception->getMessage(), [], 500);
        }
    }

    //-----------------------------------------------------------------------Api de modification des users----------------------------------------------------------
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'min:2', 'regex:/^[\pL\s]+$/u'],
                'telephone' => ['required', 'regex:/^\+?\d+$/'],
                'email' => ['required', 'email'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            ]);

            $user = User::findOrFail($id);

            $user->name = $validated['name'];
            $user->telephone = $validated['telephone'];
            $user->email = $validated['email'];

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            if ($request->hasFile('image')) {
                $encodedImage = $this->imageToBlob($request->file('image')); // Transformer l'image en base64
                $user->image = $encodedImage;
            }

            $user->save();

            return $this->jsonResponse(true, "Utilisateur modifié avec succès !", $user, 201);

        } catch (\Exception $e) {
            return $this->jsonResponse(false, 'Erreur !', $e->getMessage(),  500);
        }
    }

    //----------------------------------------------------------------------Api d'archivage des users------------------------------------------------------
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);

            $user->delete();

            return $this->jsonResponse(true, "Utilisateur supprimé avec succès", $user, 200);

        } catch (\Exception $exception) {
            return $this->jsonResponse(false, $exception->getMessage(), [], 500);
        }
    }
}
