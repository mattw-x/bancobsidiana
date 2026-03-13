<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory;

    // La clave primaria es user_id ya que es una relación 1:1 con la tabla users
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'merchant_name',
        'rif',
        'api_key',
        'webhook_url'
    ];

    /**
     * Relación: Un comercio pertenece a un usuario (Dueño)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
