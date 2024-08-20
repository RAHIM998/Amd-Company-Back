<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RetourController extends Controller
{
    //------------------------------------------------------------------Api pour lister les retours de commande------------------------------------------------------------
    public function index()
    {
        //
    }
    //-------------------------------------------------------------------Api de sauvegarde des retours--------------------------------------------------------------
    public function store(Request $request)
    {

    }

    //-------------------------------------------------------------------Api pour voir les détails de commande--------------------------------------------------------------
    public function show(string $id)
    {
        //
    }

    //---------------------------------------------------------------------Api De modification des retours------------------------------------------------------
    public function update(Request $request, string $id)
    {
        //
    }

    //---------------------------------------------------------------------Api de suppression des retours-------------------------------------------------------------
    public function destroy(string $id)
    {
        //
    }
}
