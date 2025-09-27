<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if (array_key_exists('password', $validated)) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->fill($validated)->save();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function deleteMe(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Delete all tokens for this user
        $user->tokens()->delete();
        
        // Soft delete the user account
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
            'deleted' => true
        ]);
    }

    public function adminIndex(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));
        $users = User::query()->orderBy('id', 'desc')->paginate($perPage);
        return response()->json($users);
    }

    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = bcrypt($validated['password']);
        if (array_key_exists('is_admin', $validated)) {
            $user->is_admin = (bool) $validated['is_admin'];
        }
        $user->save();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
        ], 201);
    }

    public function adminShow($id)
    {
        $userId = (int) $id;
        $user = User::onlyTrashed()->find($userId);
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'deleted_at' => $user->deleted_at,
        ]);
    }

    public function adminUpdate(Request $request, $id)
    {
        $user = User::withTrashed()->find((int) $id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // If user is soft deleted, restore them first
        if ($user->trashed()) {
            $user->restore();
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$id],
            'password' => ['sometimes', 'string', 'min:8'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('password', $validated)) {
            $validated['password'] = bcrypt($validated['password']);
        }
        $user->fill($validated)->save();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
        ]);
    }

    public function adminDestroy($id)
    {
        $user = User::find((int) $id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete(); // Soft delete
        return response()->json(['deleted' => true]);
    }

    public function adminSetAdmin(Request $request, $id)
    {
        $user = User::withTrashed()->find((int) $id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        // If user is soft deleted, restore them first
        if ($user->trashed()) {
            $user->restore();
        }
        
        $validated = $request->validate([
            'is_admin' => ['required', 'boolean'],
        ]);
        $user->is_admin = (bool) $validated['is_admin'];
        $user->save();
        return response()->json([
            'id' => $user->id,
            'is_admin' => (bool) $user->is_admin,
        ]);
    }

    public function adminDeletedUsers(Request $request)
    {
        $users = User::onlyTrashed()->orderBy('deleted_at', 'desc')->get();
        
        // Check if there are any deleted users
        if ($users->count() == 0) {
            return response()->json([
                'message' => 'No deleted users found',
                'data' => []
            ]);
        }
        
        // Format response to show only deleted users with their info
        $formattedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'deleted_at' => $user->deleted_at,
                'created_at' => $user->created_at,
            ];
        });
        
        return response()->json([
            'data' => $formattedUsers
        ]);
    }

    public function adminShowDeletedUser($id)
    {
        $user = User::onlyTrashed()->find((int) $id);
        if (!$user) {
            return response()->json(['message' => 'Deleted user not found'], 404);
        }
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'deleted_at' => $user->deleted_at,
            'created_at' => $user->created_at,
        ]);
    }

    public function adminRestoreUser($id)
    {
        $user = User::onlyTrashed()->find((int) $id);
        if (!$user) {
            return response()->json(['message' => 'Deleted user not found'], 404);
        }
        $user->restore();
        return response()->json([
            'message' => 'User restored successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
            ]
        ]);
    }

    public function adminForceDeleteUser($id)
    {
        $user = User::onlyTrashed()->find((int) $id);
        if (!$user) {
            return response()->json(['message' => 'Deleted user not found'], 404);
        }
        $user->forceDelete(); // Permanent delete
        return response()->json(['message' => 'User permanently deleted']);
    }
}


