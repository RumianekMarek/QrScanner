<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Events\UpdateUserStatus;

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
        $user_id = User::where('email', $request->email)->value('id');
        $userStatus = UserDetail::where('user_id', $user_id)->value('status');
        $password = $request->password;
        
        if($userStatus == 'blocked'){
            return redirect()->back()->with('status', 'Użytkownik nieaktywny');
        }

        $request->authenticate();

        $request->session()->regenerate();

        event(new UpdateUserStatus($user_id));

        if($userStatus == 'inactive'){
            return redirect()->route('scanner.list', ['id' => $user_id]);
        }

        return redirect()->route('scanner.create');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function token(Request $request, $token = null): RedirectResponse
    {
        if(!empty($request->targetUrl) && strpos($request->targetUrl, 'token/auth') === false){
            return back();
        }

        // dd($request, $token);
        if(empty($token)){
            $login_token = explode('/auth/', $request->targetUrl)[1];
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
