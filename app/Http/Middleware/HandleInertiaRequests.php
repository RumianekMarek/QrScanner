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
        if (!empty($request->user()->id)){
            $allScans = UserDetail::where('user_id', $request->user()->id)->value('scanner_data');
            $arrayScans = $allScans ? explode(';;', $allScans) : [];
            $lastScans = array_slice($arrayScans, -4);

            $status = UserDetail::where('user_id', $request->user()->id)->value('status');

            if($status != 'blocked'){
                $fair_meta = UserDetail::where('user_id', $request->user()->id)->value('fair_meta');
                $fair_start = Fair::where('fair_meta', $fair_meta)->value('fair_start');
                $fair_end = Fair::where('fair_meta', $fair_meta)->value('fair_end');
    
                if(!empty($fair_start) && !empty($fair_end)){
                    $today = Carbon::today();
                    $start_date = Carbon::parse($fair_start)->startOfDay();
                    $end_date = Carbon::parse($fair_end)->startOfDay();

                    if($start_date <= $today && $end_date >= $today){
                        $request->user()->status = 'active';
                    }
                } else {
                    $request->user()->status = 'inactive';
                }
            } else {
                $request->user()->status = 'blocked';
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                
            ],
            'cameraInitialized' => session()->get('camera_initialized', false),
            'lastScans' => $lastScans,
            'flash' => session()->get('status'),
        ];
    }
}
