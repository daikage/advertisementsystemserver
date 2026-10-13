<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List all users with roles.
     */
    public function index()
    {
        $users = User::orderBy('id', 'asc')->paginate(20);
        return view('users.index', compact('users'));
    }

    /**
     * Create a new team member / moderator.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => ['required', Rule::in(['admin', 'moderator', 'viewer'])],
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return back()->with('success', "User '{$request->name}' ({$request->role}) created successfully.");
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role' => ['required', Rule::in(['admin', 'moderator', 'viewer'])],
        ]);

        // Prevent self-demotion if current user is the only admin
        if ($user->id === auth()->id() && $request->role !== 'admin') {
            return back()->withErrors(['user' => 'You cannot remove your own admin privileges.']);
        }

        $user->update(['role' => $request->role]);

        return back()->with('success', "Updated role for '{$user->name}' to '{$request->role}'.");
    }

    /**
     * Delete a user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "User '{$name}' removed.");
    }
}
