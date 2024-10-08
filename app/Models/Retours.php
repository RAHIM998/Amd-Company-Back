<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Retours extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commande_id',
        'raison',
        'status'
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

}
