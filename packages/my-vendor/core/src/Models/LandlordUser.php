<?php

namespace VHAP\Core\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class LandlordUser extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * Force this model to ALWAYS use the landlord database connection.
     */
    protected $connection = 'landlord';

    protected $table = 'landlord_users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
