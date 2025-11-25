<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Fair;
use App\Models\UserDetail;
use App\Models\UserNote;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\LoginToken;
use App\Events\QrDataCurl;

class UserController extends Controller
{
    // Wyświetlanie listy użytkowników
    public function index()
    {
        $users = User::with('details')->get();
        $fairs = Fair::select('fair_meta', 'fair_name', 'fair_start', 'fair_end')
            ->whereNotNull('fair_meta')
            ->whereNotNull('fair_name')
            ->whereNotNull('fair_start')
            ->whereNotNull('fair_end')
            ->get();

        return inertia('Admin/UserList', [
            'users' => $users,
            'fairs' => $fairs,
        ]);
    }

    /**
     * Handle an incoming details request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store($id): Void
    {
        $user_details = UserDetail::create([
            'user_id' => $id,
            'status' => 'inactive',
        ]);
    }

    public function details($id)
    {
        $user = UserDetail::where('user_id', $id)->firstOrFail();

        return inertia('Admin/UserDetails', [
            'user' => $user,
        ]);
    }

    // Aktualizacja użytkownika
    public function update(Request $request)
    {
        $request->validate([
            'fair_meta' => 'required|string|max:255',
            'phone' => 'required|string|max:255|',
            'company_name' => 'required|string|max:255',
            'placement' => 'required|string|max:255',
        ]);

        UserDetail::updateOrCreate(
            ['user_id' => $request->user_id],
            $request->only(['fair_meta', 'phone', 'company_name', 'placement'])
        );

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    // Aktualizacja tokena
    public function token($id)
    {   
        $user = User::where('id', $id)->firstOrFail();

        $event = new LoginToken();
        event($event);

        $user->update(['login_token' => $event->login_token]);

        return redirect()->back();
    }

    public function status($id, $status)
    {
        $userDetail = UserDetail::where('user_id', $id)->firstOrFail();
        $newStatus = $status === 'active' ? 'inactive' : 'active';
        $userDetail->update(['status' => $newStatus]);
        
        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function block($id)
    {
        $userStatus = UserDetail::where('user_id', $id)->value('status');
        $newStatus = $userStatus === 'blocked' ? 'inactive' : 'blocked';
        UserDetail::where('user_id', $id)->update(['status' => $newStatus]);
        
        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function scanner(Request $request)
    {
        $users = User::with('details')->get();

        return inertia('Admin/UserScans', [
            'usersList' => $users,
        ]);
    }

    public function list(Request $request, $id)
    {   
        $user = User::with(['details', 'notes'])->findOrFail($id);
        $scannerData = $user->details->scanner_data ?? '';
        $userNotes = $user->notes->toArray();
        return redirect()->back()->with(
            'userData', [
                'notes' => $userNotes,
                'scannerData' => $scannerData,
            ]
        );
    }

    public function restore($id, $qrCode)
    {   
        $user = UserDetail::where('user_id', $id)->firstOrFail();

        $pattern = '/^([A-Za-z]+)(\d{3})(\d+)([A-Za-z]+)(\d+)$/';
        $entry_id = '';
        $domain_meta = '';
        
        if (preg_match($pattern, $qrCode, $matches)) {

            $domain_meta = $matches[1] . $matches[2];
            $entry_id = $matches[3];
        } else {
            $qrCode = substr(preg_replace('/\s+/', '', $qrCode), 0, 32);
        }
        $domain = Fair::where('qr_details', 'LIKE', '%'. $domain_meta . '%')->get('domain');

        if(!$domain->isNotEmpty()){
            return redirect()->back()->with('message', 'Qr Code Prefix nie został odnaleziony, sprawdź czy targi zostały dodane');
        }

        $event = new QrDataCurl($domain, $entry_id, $qrCode);
        event($event);
        
        if($event->returner === null){
            foreach($domain as $index => $val){
                $domain[$index]->domain = 'old.' . $val->domain;
            }
            $event = new QrDataCurl($domain, $entry_id, $qrCode);
            event($event);
        }

        $data = $event->returner->data ?? new \stdClass();
        $data->qrCode = $qrCode;
        $data->status = $event->returner->status ?? "false";

        $oldScann = UserDetail::where('user_id', $id)->value('scanner_data');
        $updatedScann = '';

        if($data->status != "false"){
            $event_data = json_encode($event->returner->data);
            $updatedScann = preg_replace_callback(
                '/\{[^{}]*"qrCode":"' . preg_quote($qrCode, '/') . '"[^{}]*\}/',
                function ($match) use ($event_data) {
                    return $event_data;
                },
                $oldScann
            );

            $newScann = UserDetail::where('user_id', $id)->value('scanner_data');
            if($newScann == $oldScann){
                UserDetail::where('user_id', $id)->update(['scanner_data' => $updatedScann]);
            }
        }
        return redirect()->back()->with('scannerData', $updatedScann);
    }
}