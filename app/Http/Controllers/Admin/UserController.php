<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Fair;
use App\Models\UserDetail;
use App\Models\UserNote;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Events\LoginToken;
use App\Events\QrDataCurl;
use App\Events\NewQrDataCurl;
use App\Events\CartPolandQrDataCurl;

class UserController extends Controller
{
    // Wyświetlanie listy użytkowników
    public function index()
    {
        $users = User::with('details')
            ->latest()
            ->get();

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

    public function scanner(Request $request, $id = '')
    {
        $users = User::with('details')->latest()->get();
        if(!empty($id)){
            $user = User::with(['details', 'notes'])->findOrFail($id);
            $scannerData = $user->details->scanner_data ?? '';
            $userNotes = $user->notes->toArray();
            $userData = [
                'notes' => $userNotes,
                'scannerData' => $scannerData,
            ];
        } else {
            $userData = null;
        }
        return inertia('Admin/UserScans', [
            'usersList' => $users,
            'userData' => $userData,
            'selectedUser' => $id,
        ]);
    }

    public function list(Request $request, $id)
    {   
        $user = User::with(['details', 'notes'])->findOrFail($id);
        $scannerData = $user->details->scanner_data ?? '';
        $userNotes = $user->notes->toArray();

        return response()->json([
            'userData' => [
                'notes' => $userNotes,
                'scannerData' => $scannerData,
            ]
        ]);
    }

    public function getData($id, $qrCode)
    {   
        Log::info(json_encode($qrCode));
        $data =  new \stdClass();
        $domain_meta = substr($qrCode, 0 , 7);
        $qrParts = explode('rnd', strtolower($qrCode));
        $entry_id = str_replace(strtolower($domain_meta), '', $qrParts[0]);

        $event = new CartPolandQrDataCurl($qrCode);
        $eventData = $event->returner[0] ?? [];

        if(!empty($eventData)){
            $interArr = [];
            for($i = 1; $i<10; $i++){
                $interArr[] = $eventData['zainteresowania' . $i];
            }

            $inter = array_filter(array_map(function($val) {
                return trim(preg_replace('/(Inne|\r|\n|<.*?>|,|^Tak$|^Nie$)/', ' ', $val));
            }, $interArr));

            $data->company = $eventData['company'] ?? $eventData['nip'] ?? '';
            $data->name = Str::title(($eventData['imie'] ?? '') . '  ' . ($eventData['nazwisko'] ?? ''));
            $data->email = $eventData['email'] ?? '';
            $data->phone = $eventData['telefon'] ?? '';
            $data->adress = $eventData['ulica'] . ' ' . $eventData['numer'] . ' ' . $eventData['miasto'] . ' ' . $eventData['kod_pocztowy'] . ' ' . $eventData['kraj'];
            $data->interests = preg_replace('/\s+/', ' ', implode(' ', $inter));
            $data->status = 'true';

        } else if(preg_match('/\d+w\d+/', $entry_id)){
            $event = new NewQrDataCurl($qrCode);
            $eventData = $event->returner->data->person;

            $data->company = $eventData->company ?? '';
            $data->name = $eventData->fullName ?? '';
            $data->email = $eventData->email ?? '';
            $data->phone = $eventData->phone ?? '';
            $data->status = ($event->returner->success ?? '') ? 'true' : 'false';            
        } else {
            $domain = Fair::where('qr_details', 'LIKE', '%'. ($domain_meta  . ',') . '%')->pluck('domain');
            if($domain->isNotEmpty() && !empty($qrParts[0]) && !empty($qrParts[1])){
                
                $event = new QrDataCurl($domain, $entry_id, $qrCode);
                event($event);
                
                if($event->returner === null){
                    foreach($domain as $index => $val){
                        $domain[$index]->domain = 'old.' . $val->domain;
                    }
                    $event = new QrDataCurl($domain, $entry_id, $qrCode);
                    event($event);
                }
                
                $eventData = $event->returner->data ?? (object)[];

                $data->company = $eventData->company ?? '';
                $data->name = $eventData->name ?? '';
                $data->email = $eventData->email ?? '';
                $data->phone = $eventData->phone ?? '';
                $data->status = $event->returner->status ?? '';
            } else {
                $user = User::with(['details', 'notes'])->findOrFail($id);
                $scannerData = $user->details->scanner_data ?? '';
                $userNotes = $user->notes->toArray();
    
                return redirect()->back()
                    ->with(
                        'userData', [
                            'notes' => $userNotes,
                            'scannerData' => $scannerData,
                        ]
                    )
                    ->with('message', 'Qr Code Prefix nie został odnaleziony, sprawdź czy targi zostały dodane');
            }
        }
        Log::info(json_encode($data));
        $data->qrCode = $qrCode;

        return $data;
    }

    public function restore($id, $qrCode)
    {
        $entry_id = '';
        $event = null;
        
        $data = $this->getData($id, $qrCode);
        if(empty($data->email)) $data = '{"status":"false","qrCode":"' . $qrCode . '"}';

        $updatedScann = '';
        if($data->status != "false"){
            $event_data = json_encode($data);
            $oldScann = UserDetail::where('user_id', $id)->value('scanner_data');

            $updatedScann = preg_replace_callback(
                '/\{[^{}]*"qrCode":"' . preg_quote($qrCode, '/') . '"[^{}]*\}/',
                function ($match) use ($event_data) {
                    return $event_data;
                },
                $oldScann
            );

            if ($updatedScann !== $oldScann) {
                UserDetail::where('user_id', $id)->update(['scanner_data' => $updatedScann]);
            }
            
            $user = User::with(['details', 'notes'])->findOrFail($id);
            $scannerData = $user->details->scanner_data ?? '';
            $userNotes = $user->notes->toArray();
            
            return response()->json([
                    'userData' => [
                        'notes' => $userNotes,
                        'scannerData' => $scannerData,
                    ],
                    'message' => 'QR code poprawnie odnaleziony'
                ]);
        }
        
    }

    public function allRestore($id)
    {
        $userDetail = UserDetail::where('user_id', $id)->first();

        if (!$userDetail || empty($userDetail->scanner_data)) return;

        \Log::channel('scanner_backup')->info("START DATA: " . $id);
        \Log::channel('scanner_backup')->info("RAW DATA: " . $userDetail->scanner_data);

        $dataArray = explode(';;', $userDetail->scanner_data);
        $newData = [];

        foreach($dataArray as $single) {
            try{
                $sData = json_decode(trim($single), true);
                $newD = $this->getData($id, $sData['qrCode']);

                if(empty($newD->email)) $newD = '{"status":"false","qrCode":"' . $qrCode . '"}';

                $newData[] = json_encode($newD, JSON_UNESCAPED_UNICODE);
            } catch(\Throwable $e) {
                $newData[] = $single;
                \Log::error("Błąd przy All Restore: " . $e->getMessage());
            }
        }

        if (!empty($newData)) {
            \Log::channel('scanner_backup')->info("FINAL DATA: " . implode(';; ', $newData));
            \Log::channel('scanner_backup')->info("END Update User: " . $id . "\n" . str_repeat('=', 40));

            $userDetail->update(['scanner_data' => implode(';; ', $newData)]);

            $user = User::with(['details', 'notes'])->findOrFail($id);
            $scannerData = $user->details->scanner_data ?? '';
            $userNotes = $user->notes->toArray();

            return response()->json([
                    'userData' => [
                        'notes' => $userNotes,
                        'scannerData' => $scannerData,
                    ],
                    'message' => 'QR code poprawnie odnaleziony'
                ]);
        }

    }
}