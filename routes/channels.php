<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user->isAdmin(); // Vérifie si l'utilisateur est un administrateur
});
