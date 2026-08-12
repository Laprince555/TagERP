<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class CreateUserForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $successMessage = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    public function submit(): void
    {
        Gate::authorize('manage_users');

        $validated = $this->validate();

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'theme' => 'orange-onyx',
            ]);

            $role = Role::findOrCreate('super_admin', 'web');

            $user->assignRole($role);
        });

        $this->reset(['name', 'email', 'password', 'password_confirmation']);
        $this->successMessage = __('User created successfully with the super_admin role.');
    }

    public function render(): View
    {
        return view('livewire.admin.create-user-form');
    }
}
