<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Log;


class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%$search%")
                ->orWhere('address', 'like', "%$search%");
        }

        $users = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
            'message' => 'Users fetched successfully'
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'address' => 'required|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'address']);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->storeAs('images', 'user_' . Str::random(10) . '.' . $image->getClientOriginalExtension());
            $data['image'] = $path;
        }

        $user = User::create($data);

        return response()->json([
            'status' => 'success',
            'data' => $user,
            'message' => 'User created successfully'
        ], 201);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user,
            'message' => 'User fetched successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validatedData = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:50',
            'address' => 'sometimes|required|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validatedData->fails()) {
            return response()->json(['success' => false, 'message' => $validatedData->errors()], 400);
        }

        // Handle image file upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($user->image && Storage::exists($user->image)) {
                Storage::delete($user->image);
            }

            // Store new image
            $image = $request->file('image');
            $fileName = 'user_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('images', $fileName);
            $user->image = $path;
        }

        // Update user data except 'image'
        $user->update($request->except('image'));

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully!',
            'updated_data' => $user
        ], 200);
    }


    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        if ($user->image && Storage::exists($user->image)) {
            Storage::delete($user->image);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
}