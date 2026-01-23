<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserNote;
use App\Models\Fair;
use App\Events\QrDataCurl;
use Illuminate\Support\Facades\Mail;
use App\Mail\CsvEmail;

class ScannerController extends Controller
{
    public function create(?string $mode = 'camera'): Response
    {
        $userId = auth()->user()->id;
        $user = User::with(['details', 'notes'])->findOrFail($userId);
        $scannerData = $user->details->scanner_data;
        $userNotes = $user->notes;

        return Inertia::render('Scanner/Scanner', [
            'mode' => $mode,
            'userNotes' => $userNotes,
        ]);
    }

    public function list($id): Response
    {   
        $user = User::with(['details', 'notes'])->findOrFail($id);
        $scannerData = $user->details->scanner_data;
        $userNotes = $user->notes;

        return Inertia::render('Scanner/ScannedList', [
            'scannerData' => $scannerData,
            'userNotes' => $userNotes,
            'user' => $user,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request): RedirectResponse
    {   
        $request->validate ([
            'qrCode' => 'required|string|max:255',
        ]);

        $user = UserDetail::where('user_id', $request->user()->id)->firstOrFail();

        $entry_id = '';
        $domain_meta = '';

        $qrCode = $request->qrCode;

        $qrParts = explode('rnd', $qrCode);
        $maxLength = strlen($qrParts[0]);
        $match = false;
        $i = 0;

        while($match == false){
            $first = substr($qrParts[0], $i);
            $fLen = strlen($first);
            $sec = substr($qrParts[1], -$fLen);
            $bool = ($first == $sec);
            $first = $first;
            $sec = $sec;
            if($first == $sec){
                $entry_id = $first;
                $domain_meta = substr($qrParts[0],0 ,  $i);
                $match = true;
            } else {
                $match = false;
                $i++;
            }
        }
        
        $domain = Fair::where('qr_details', 'LIKE', '%'. $domain_meta . '%')->get('domain');

        $event = new QrDataCurl($domain, $entry_id, $qrCode);
        event($event);
        
        $data = $event->returner->data ?? new \stdClass();
        $data->qrCode = $qrCode;
        $data->status = $event->returner->status ?? "false";

        $event_data = json_encode($data);
        
        $scannerDetails = $user->scanner_data . $event_data . ';; ';
        
        $user->update([
            'scanner_data' => $scannerDetails,
        ]);

        return back()->with('status', $data);
    }

    public function download($id)
    {
        $user_data = User::with(['details', 'notes'])->findOrFail($id);
        $data = $user_data->details->scanner_data;
        $notes = $user_data->notes;

        $notesColl = $notes->map->only(['qr_code', 'note']);

        $data_array = explode(';;', $data);
        $csv_data = "id,Email,Telefon,Imie i Nazwisko, ScanedCode, Notatka \n";

        foreach($data_array as $index => $single){
            if(trim($single) == ""){ continue; }

            $single = json_decode($single);

            $singleNote = $notesColl->firstWhere('qr_code', $single->qrCode ?? null);

            $csv_data .= $index + 1;
            $csv_data .= ',' . ($single->email ?? ' ');
            $csv_data .= ',' . ($single->phone ?? ' ');
            $csv_data .= ',' . ($single->name ?? ' ');
            $csv_data .= ',' . ($single->qrCode ?? ' ');
            $csv_data .= ',' . ($singleNote['note'] ?? ' ');
            $csv_data .= "\n";
        }

        return back()->with('status',  $csv_data);
    }

    public function send(Request $request)
    {
        $userEmail = $request->user()->email;
        $csvData = $request->csvData;

        Mail::send([], [], function ($message) use ($csvData, $userEmail) {
            $message->to($userEmail)
                ->subject('Your CSV File') // Temat wiadomości
                ->html('<p>Hello,</p><p>Please find the attached CSV file.</p>')
                ->attachData($csvData, 'data.csv', [
                    'mime' => 'text/csv',
                ]);
        });
        
        return back()->with('message', 'E-mail sent successfully.');
    }

    public function saveNote(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'qr_code' => 'required',
            'note' => 'required|string',
        ]);

        UserNote::updateOrCreate(
            ['user_id' => $request->user_id, 'qr_code' => $request->qr_code],
            $request->only(['note']),
        );
        
        return back()->with('message', 'Notatka zapisane poprawnie.');
    }
}
