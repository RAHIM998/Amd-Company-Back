<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //Fonction de rsponse json
    protected function jsonResponse(bool $success, string $message = null, $data = [], int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    //Encodage de l'image
    public function imageToBlob($images){
        $file= fopen($images->getRealPath(), 'rb');
        $content = stream_get_contents($file);
        fclose($file);
        return base64_encode($content);
    }
}
