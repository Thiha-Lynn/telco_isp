<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified',
        'role_id',
        'image',
        'password',
        'uniqid',
        'phone',
        'sub_company',
        'user_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'password' => 'hashed',
        ];
    }

    /**
     * Get the role permissions for this admin.
     * Queries the permission_role pivot table to get permission IDs.
     * Returns a collection compatible with the view's expected format.
     */
    public function get_role(): \Illuminate\Support\Collection
    {
        $roleId = Auth::guard('admin')->user()?->role_id;
        
        if (!$roleId) {
            return collect();
        }
        
        return DB::table('permission_role')
            ->where('role_id', $roleId)
            ->get(['permission_id']);
    }

    /**
     * Get the role relationship.
     */
    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
