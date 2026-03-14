<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasManyThrough; // <--- IMPORTANTE
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function accounts() {
        return $this->hasMany(Account::class);
    }

    // "Un cliente tiene muchas tarjetas" (a través de sus cuentas)
    public function cards(): HasManyThrough
    {
        // Esto le dice a Laravel:
        // "Busca las tarjetas que pertenecen a las cuentas de este usuario"
        return $this->hasManyThrough(Card::class, Account::class);
    }

    public function merchants()
    {
        return $this->hasMany(Merchant::class, 'user_id', 'id');
    }
    // Relaciones útiles para el panel
    public function account()
    {
        return $this->hasOne(Account::class);
    }

}
