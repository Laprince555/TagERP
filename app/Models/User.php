<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\HR\Models\EmployeeManagement\Employee;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'theme', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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

    public function getThemeClass(): string
    {
        return 'theme-'.($this->theme ?: 'orange-onyx');
    }

    /**
     * Nullable by design: an admin account (managing-company staff) may have
     * no Employee row at all — see Modules\HR\Models\EmployeeManagement\Employee,
     * which owns the FK (employees.user_id, nullable, unique).
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }
}
