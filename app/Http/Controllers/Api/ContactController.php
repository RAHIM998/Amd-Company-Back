<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validation des données du formulaire
            $validated = $request->validate([
                'name' => ['required', 'min:2', 'regex:/^[\pL\s]+$/u'],
                'email' => 'required|email|max:255',
                'object' => 'required|string|max:255',
                'message' => 'required|string',
                'phone' => ['required', 'regex:/^(\+\d{1,4})?\d+$/'],
            ]);

            // Enregistrement du message de contact dans la base de données
            $contact = Contact::create($validated);

            /*/ Envoi de l'email de notification (à toi-même ou à une adresse spécifiée)
            Mail::send('emails.contact', compact('contact'), function ($message) use ($contact) {
                $message->to('ton.email@example.com')
                    ->subject('Nouveau message de contact : ' . $contact->subject);
            });*/

            // Redirection avec un message de succès
            return $this->jsonResponse(true, 'Merci de nous avoir contacté. Nous vous répondrons bientôt', $contact, 201);
        }catch (\Exception $exception){
            return $this->jsonResponse(false, 'Error!', $exception, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
