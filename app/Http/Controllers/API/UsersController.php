<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class UsersController extends Controller
{

    public function update(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401); // HTTP 401 Unauthorized
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . auth()->user()->id,
            'old_password' => 'string|min:8', // Add validation for old password
            'new_password' => 'string|min:8', // Validation for new password and confirmation
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422); // HTTP 422 Unprocessable Entity
        }

        // Verify old password if provided
        if ($request->has('old_password') && !Hash::check($request->input('old_password'), $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 422);
        }

        // Update user data
        $user->name = $request->input('name');
        $user->email = $request->input('email');

        if ($request->has('new_password')) {
            $user->password = Hash::make($request->input('new_password'));
        }

        $user->save();

        return response()->json(['user' => $user, 'message' => 'User updated successfully'], 200); // HTTP 200 OK
    }

    public function updatePrefrences(Request $request)
    {
        // Validate the request data
        $this->validate($request, [
            'favorite_sources' => 'array',
            'favorite_categories' => 'array',
            'favorite_authors' => 'array',
        ]);

        // Get the authenticated user
        $user = User::find(auth()->user()->id);
        // Update the user's preferences
        $user->preferences = [
            'favorite_sources' => $request->input('favorite_sources'),
            'favorite_categories' => $request->input('favorite_categories'),
            'favorite_authors' => $request->input('favorite_authors'),
        ];
        $user->save();

        return response()->json(['message' => 'Preferences updated successfully', 'user' => $user]);
    }
}
