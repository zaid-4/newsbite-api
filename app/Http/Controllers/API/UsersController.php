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
    public function index()
    {
        $users = User::all();
        return response()->json(['users' => $users], 200); // HTTP 200 OK
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404); // HTTP 404 Not Found
        }

        return response()->json(['user' => $user], 200); // HTTP 200 OK
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404); // HTTP 404 Not Found
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $id,
            'old_password' => 'string|min:8', // Add validation for old password
            'password' => 'string|min:8|confirmed', // Validation for new password and confirmation
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

        if ($request->has('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return response()->json(['user' => $user, 'message' => 'User updated successfully'], 200); // HTTP 200 OK
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404); // HTTP 404 Not Found
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200); // HTTP 200 OK
    }
}
