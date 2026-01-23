<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\UserDetail;
use App\Models\Fair;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $lastScans = '';
        $userStatus = 'inactive'; // Domyślny status

        if (!empty($request->user()->id)) {
            // Pobierz ostatnie skany
            $allScans = UserDetail::where('user_id', $request->user()->id)->value('scanner_data');
            $arrayScans = $allScans ? explode(';;', $allScans) : [];
            $lastScans = array_slice($arrayScans, -4);
            
            $userStatus = UserDetail::where('user_id', $request->user()->id)->value('status');
        }

        $sessionIndex = isset(session()->get('_flash.old')[0]) ? session()->get('_flash.old')[0] : null;
        $sessionData = isset($sessionIndex) ? session()->get($sessionIndex) : '';

        // Zwrócenie danych do props
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'status' => $userStatus,
            ],
            'cameraInitialized' => session()->get('camera_initialized', false),
            'lastScans' => $lastScans,
            'flash' => session('status'),
            'message' => session('message'),
            ...($sessionIndex !== null ? [$sessionIndex => $sessionData] : []),
        ];
    }
}
