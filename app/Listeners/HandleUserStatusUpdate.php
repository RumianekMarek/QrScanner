<?php

namespace App\Listeners;

use App\Events\UpdateUserStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\UserDetail;
use App\Models\Fair;
use Carbon\Carbon;

class HandleUserStatusUpdate
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UpdateUserStatus $event): void
    {
        $user_id = $event->user_id;

        // Pobierz dane użytkownika
        $userDetail = UserDetail::where('user_id', $user_id)->first();

        if ($userDetail) {

            $fair_start = Fair::where('fair_meta', $userDetail->fair_meta)->value('fair_start');
            $fair_end = Fair::where('fair_meta', $userDetail->fair_meta)->value('fair_end');

            if (!empty($fair_start) && !empty($fair_start)) {

                $today = Carbon::today();
                $start_date = Carbon::parse($fair_start)->startOfDay();
                $end_date = Carbon::parse($fair_end)->startOfDay();

                // Oblicz status użytkownika
                $status = ($start_date <= $today && $end_date >= $today) ? 'active' : 'inactive';

                // Zapisz status w bazie
                $userDetail->status = $status;
                $userDetail->save();

            } else {
                
                $userDetail->status = 'inactive';
                $userDetail->save();
            }
        }
    }
}
