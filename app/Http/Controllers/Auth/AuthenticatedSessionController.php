<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->route('scanner.create');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function token(Request $request, $token = null): RedirectResponse
    {
        if(empty($token)){
            $login_token = explode('/auth/', $request->token)[1];
        } else {
            $login_token = $token;
        }

        $user = User::where('login_token', $login_token)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Nieprawidłowy token']);
        }
    
        Auth::login($user);

        session()->regenerate();

        return redirect()->route('scanner.create', [
            'mode' => 'device',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
