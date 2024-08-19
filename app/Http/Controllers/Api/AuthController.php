<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Token;
use Mockery\Exception;
use Laravel\Passport\PersonalAccessTokenResult;

class AuthController extends Controller
{
    //-------------------------------------------------------------------------Api de connexion---------------------------------------------------------------
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:4'
        ]);
        try {
            $credentials = $request->only(['email', 'password']);
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                return $this->jsonResponse(true, 'Connexion avec succès !', [
                    'user' => $user,
                    'token' => $user->createToken('Amdcompany')->accessToken,
                ]);
            }else {
                return $this->jsonResponse(false,  'Identifiants incorrects', [], 401);
            }
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }

    }

    //-----------------------------------------------------------------------Api de création des clients-------------------------------------------------------
    public function register(UserRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validatedData = $request->validated();

            if ($request->hasFile('image')) {
                $image = $this->imageToBlob($request->file('image'));
                $validatedData['image'] = $image;
            }

            $users = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'telephone' => $validatedData['telephone'],
                'image' => $validatedData['image'],
                'password' => Hash::make($validatedData['password']),
                'role' => "user",
            ]);


            $token = $users->createToken('auth_token')->accessToken;

            return $this->jsonResponse(true, 'Utilisateur créé avec succès !', [
                'access_token' => $token,
                'user' => $users,
                'token_type' => 'Bearer',
            ], 201);

        } catch (\Exception $exception) {

            return $this->jsonResponse(false, $exception->getMessage(), [], 500);
        }
    }

    //-------------------------------------------------------------------------Api de deconnexion--------------------------------------------------------
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $accessToken = $user->token();

            Token::where('id', $accessToken->id)->update(['revoked' => true]);

            $accessToken->revoke();
            return $this->jsonResponse(true, 'Vous êtes maintenant deconnectés !', []);

        }catch (Exception $exception){
            return $this->jsonResponse(false, 'Erreur !', $exception->getMessage(), 500);
        }

    }

}
