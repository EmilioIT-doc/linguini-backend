<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\UserAddress;

class UserController extends Controller
{
    // =========================
    // REGISTER
    // =========================
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            // phone y role opcionales si ya existen en DB
        ]);

        return response()->json(['message' => 'Registered successfully'], 201);
    }

    // =========================
    // LOGIN
    // =========================
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        // ✅ recomendado para tu frontend
        $user->load('addresses');

        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'addresses' => $user->addresses,
            ],
        ]);
    }

    // =========================
    // GET USER PROFILE + ADDRESSES
    // GET /api/user
    // =========================
    public function user(Request $request)
    {
        $user = $request->user()->load('addresses');

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'addresses' => $user->addresses,
        ]);
    }

    // =========================
    // UPDATE USER (name + phone)
    // PATCH /api/user
    // =========================
    public function updateUser(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'required|string|max:120',
            'phone' => 'nullable|string|max:25',
        ]);

        $user->update([
            'name'  => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        $user->load('addresses');

        return response()->json([
            'message' => 'Perfil actualizado.',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'addresses' => $user->addresses,
            ],
        ]);
    }

    // =========================
    // ADDRESSES - INDEX
    // GET /api/user/addresses
    // =========================
    public function addressesIndex(Request $request)
    {
        $addresses = UserAddress::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'addresses' => $addresses,
        ]);
    }

    // =========================
    // ADDRESSES - STORE
    // POST /api/user/addresses
    // =========================
    public function store(Request $request)
    {
        $data = $request->validate([
            'label'           => 'required|string|max:50',
            'street'          => 'required|string|max:150',
            'exterior_number' => 'required|string|max:20',
            'interior_number' => 'nullable|string|max:20',
            'neighborhood'    => 'required|string|max:120',
            'references'      => 'required|string|max:255',
        ]);

        $address = UserAddress::create([
            'user_id'         => $request->user()->id,
            'label'           => $data['label'],
            'street'          => $data['street'],
            'exterior_number' => $data['exterior_number'],
            'interior_number' => $data['interior_number'] ?? null,
            'neighborhood'    => $data['neighborhood'],
            'references'      => $data['references'],
        ]);

        return response()->json([
            'message' => 'Dirección creada.',
            'address' => $address,
        ], 201);
    }

    // =========================
    // ADDRESSES - UPDATE
    // PATCH /api/user/addresses/{id}
    // =========================
    public function update(Request $request, $id)
    {
        // ✅ seguridad: solo puede editar sus direcciones
        $address = UserAddress::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'label'           => 'required|string|max:50',
            'street'          => 'required|string|max:150',
            'exterior_number' => 'required|string|max:20',
            'interior_number' => 'nullable|string|max:20',
            'neighborhood'    => 'required|string|max:120',
            'references'      => 'required|string|max:255',
        ]);

        $address->update([
            'label'           => $data['label'],
            'street'          => $data['street'],
            'exterior_number' => $data['exterior_number'],
            'interior_number' => $data['interior_number'] ?? null,
            'neighborhood'    => $data['neighborhood'],
            'references'      => $data['references'],
        ]);

        return response()->json([
            'message' => 'Dirección actualizada.',
            'address' => $address->fresh(),
        ]);
    }

    // =========================
    // ADDRESSES - DESTROY
    // DELETE /api/user/addresses/{id}
    // =========================
    public function destroy(Request $request, $id)
    {
        $address = UserAddress::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $address->delete();

        return response()->json([
            'message' => 'Dirección eliminada.',
        ]);
    }

    // =========================
    // LOGOUT
    // =========================
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
