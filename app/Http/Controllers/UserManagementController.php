<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,operator'],
            'can_access_admin' => ['nullable', 'boolean'],
            'can_access_app' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isAdminRole = $validated['role'] === 'admin';

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'can_access_admin' => $isAdminRole ? true : $request->boolean('can_access_admin'),
            'can_access_app' => $request->boolean('can_access_app', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLog::record('USER_CREATED', "Created user '{$user->name}' ({$user->email}) with role: {$user->role}. App Access: ".($user->can_access_app ? 'Yes' : 'No'));

        return redirect()->route('users.index')->with('success', "User '{$user->name}' created successfully.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:admin,operator'],
            'can_access_admin' => ['nullable', 'boolean'],
            'can_access_app' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isAdminRole = $validated['role'] === 'admin';

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'can_access_admin' => $isAdminRole ? true : $request->boolean('can_access_admin'),
            'can_access_app' => $request->boolean('can_access_app'),
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        ActivityLog::record('USER_UPDATED', "Updated user '{$user->name}'. Role: {$user->role}, App Access: ".($user->can_access_app ? 'Yes' : 'No').', Active: '.($user->is_active ? 'Yes' : 'No'));

        return redirect()->route('users.index')->with('success', "User '{$user->name}' permissions updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::record('USER_DELETED', "Deleted user '{$name}'.");

        return redirect()->route('users.index')->with('success', "User '{$name}' deleted successfully.");
    }
}
