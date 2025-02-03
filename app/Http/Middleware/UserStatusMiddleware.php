<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\UserDetail;

class UserStatusMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()->admin) {
            return $next($request);
        }

        $allowedRoutes = [
            'scanner.list',
        ];

        $user_id = $request->user()->id;
        $userStatus = UserDetail::where('user_id', $user_id)->value('status');
        
        if($userStatus) {
            if($userStatus == 'inactive' && !in_array($request->route()->getName(), $allowedRoutes)){
                return redirect()->route('scanner.list', [$user_id])->with('message', 'brak dostępu do skanera');
            }

            if($userStatus == 'blocked'){
                return redirect()->route('logout')->with('message', 'użytkownik zablokowany');
            }
            return $next($request);
        }
    }
}
