<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use App\Models\Login;

class LoginController extends Controller
{
    public function login()
    {
        return view('login.login');
    }

    public function error()
    {
        return view('error');
    }

    public function getLogin(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (Auth::attempt($validatedData)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        } else {
            return redirect()->back()->with('error', 'Login gagal, periksa kembali username dan password.');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/dashboard');
    }

    public function addUser(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'username' => 'required',
            'password' => 'required'
        ]);

        $password = Hash::make($validatedData['password']);

        $post = new Login([
            'name' => $validatedData['name'],
            'username' => $validatedData['username'],
            'password' => $password,
            'role' => 'administrator',
            'created_at' => now(),
            'last_login' => now()
        ]);

        $post->save();

        return response()->json($post);
    }
}
