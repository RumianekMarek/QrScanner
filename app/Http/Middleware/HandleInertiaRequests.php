<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\UserDetail;

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
        if (!empty($request->user()->id)){
            $allScans = UserDetail::where('user_id', $request->user()->id)->value('scanner_data');
            $arrayScans = $allScans ? explode(';;', $allScans) : [];
            $lastScans = array_slice($arrayScans, -4);
        }


        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                
            ],
            'lastScans' => $lastScans,
            'flash' => session()->get('status'),
        ];
    }
}
