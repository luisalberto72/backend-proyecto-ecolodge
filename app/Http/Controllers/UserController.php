<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Mail\ResetPasswordEmail;
use Illuminate\Support\Str;
use App\Models\User;

class UserController extends Controller
{
    public function register(Request $request)
    {
        \Log::info('Datos recibidos:', $request->all());

        $validatedData = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'address' => 'nullable|string',
            'gender' => 'nullable|string',
            'nationality' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'role' => 'required|string',
        ]);

        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'address' => $validatedData['address'] ?? null,
            'gender' => $validatedData['gender'] ?? null,
            'nationality' => $validatedData['nationality'] ?? null,
            'phone_number' => $validatedData['phone_number'] ?? null,
            'role' => $validatedData['role'],
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
    
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 0,
                    'code' => 401,
                    'data' => null,
                    'message' => 'Invalid credentials'
                ]);
            }
    
            // Si quieres asegurarte de que el campo `iss` sea correcto:
            $token = JWTAuth::fromUser(auth()->user(), ['iss' => 'http://localhost:8000/api/login']);
    
        } catch (JWTException $e) {
            return response()->json([
                'code' => 500,
                'data' => null,
                'message' => 'Could not create token'
            ]);
        }
    
        return response()->json([
            'status' => 1,
            'code' => 200,
            'data' => [
                'token' => $token,
                'first_name' => auth()->user()->first_name,
                'last_name' => auth()->user()->last_name,
                'email' => auth()->user()->email,
                
            ],
            'message' => 'Login successful'
        ]);
    }
    

    public function userinfo($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }



    public function updateProfilePicture(Request $request, $id)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        $user = User::findOrFail($id);
    
        if ($request->hasFile('profile_picture')) {
            // Guardar la imagen en storage/app/public/dashboard/
            $imagePath = $request->file('profile_picture')->store('public/dashboard');
    
            // Obtener solo el nombre del archivo
            $imageName = basename($imagePath);
    
            // Eliminar la imagen anterior si existe
            if ($user->profile_picture && Storage::exists('public/dashboard/' . $user->profile_picture)) {
                Storage::delete('public/dashboard/' . $user->profile_picture);
            }
    
            // Guardar el nombre de la imagen en la base de datos
            $user->profile_picture = $imageName;
            $user->save();
        }
    
        return response()->json(['message' => 'Imagen subida correctamente', 'profile_picture' => $imageName]);
    }


    public function updateUserProfile(Request $request, $userId)
{
    $user = User::findOrFail($userId);

    // Validar los datos
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'address' => 'nullable|string',
        'gender' => 'nullable|string',
        'nationality' => 'nullable|string',
        'phone_number' => 'nullable|string|max:15',
        'email' => 'required|email|unique:users,email,' . $userId,
        'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // Validación de imagen
    ]);

    // Actualizar los campos de texto
    $user->update([
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'address' => $request->address,
        'gender' => $request->gender,
        'nationality' => $request->nationality,
        'phone_number' => $request->phone_number,
        'email' => $request->email,
    ]);

    // Subir la imagen si está presente
    if ($request->hasFile('profile_picture')) {
        $imagePath = $request->file('profile_picture')->store('public/dashboard');
        $user->profile_picture = basename($imagePath);
        $user->save();
    }

    // Retornar la respuesta
    return response()->json([
        'message' => 'Perfil actualizado correctamente',
        'profile_picture' => asset('storage/dashboard/' . $user->profile_picture), // URL de la imagen actualizada
    ]);
}

   public function updateUser(Request $request, $id)
{
    $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'address' => 'nullable|string|max:255',
        'gender' => 'nullable|string|max:10',
        'nationality' => 'nullable|string|max:255',
        'phone_number' => 'nullable|numeric',
        'email' => 'required|email|max:255',
        'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $user = User::findOrFail($id);

    if ($request->hasFile('profile_picture')) {
        $file = $request->file('profile_picture');
        $fileName = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('profile_pictures', $fileName, 'public');

        $user->profile_picture = $filePath;
    }

    $user->update($request->only([
        'first_name', 'last_name', 'address', 'gender', 'nationality', 'phone_number', 'email'
    ]));

    return response()->json(["message" => "Perfil actualizado correctamente", "user" => $user], 200);
}

    
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Email not found'], 404);
        }

        $token = Str::random(60);
        $this->savePasswordResetToken($user->email, $token);

        $resetLink = config('app.url') . '/reset-password?token=' . $token;

        return response()->json(['message' => $resetLink]);
    }

    protected function savePasswordResetToken($email, $token)
    {
        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            ['token' => $token, 'created_at' => now()]
        );
    }

    protected function ValidateToken(Request $request)
    {
        $exists = User::where('id', $request->user_id)
                      ->where('email', $request->email)
                      ->exists();

        return response()->json($exists ? "Token is valid" : "Token is not valid");
    }

    public function refreshToken()
{
    try {
        return response()->json([
            'access_token' => auth()->refresh(),
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => 'No se pudo renovar el token'], 401);
    }
}


}
