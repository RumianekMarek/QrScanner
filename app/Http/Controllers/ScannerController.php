<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\UserDetail;
use App\Models\Fair;
use App\Events\QrDataCurl;
use Illuminate\Support\Facades\Mail;
use App\Mail\CsvEmail;

class ScannerController extends Controller
{
    public function create(?string $mode = 'camera'): Response
    {
        return Inertia::render('Scanner/Scanner', [
            'mode' => $mode,
        ]);
    }

    public function list($id): Response
    {   
        $scannerData = UserDetail::where('user_id', $id)->value('scanner_data');

        return Inertia::render('Scanner/ScannedList', [
            'scannerData' => $scannerData,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request): RedirectResponse
    {   
        // dd($request);
        $request->validate ([
            'qrCode' => 'required|string|max:255',
        ]);

        $user = UserDetail::where('user_id', $request->user()->id)->firstOrFail();

        $pattern = '/^([A-Za-z]+)(\d{3})(\d+)(rnd)(\d+)$/';
        $entry_id = '';
        $domain_meta = '';

        $qrCode = $request->qrCode;

        if (preg_match($pattern, $qrCode, $matches)) {
            $domain_meta = $matches[1] . $matches[2];
            $entry_id = $matches[3];
        } 
        
        $domain = Fair::where('qr_details', 'LIKE', '%'. $domain_meta . '%')->get('domain');
        $event = new QrDataCurl($domain, $entry_id, $qrCode);
        event($event);

        $data = $event->returner->data ?? new \stdClass();
        $data->qrCode = $qrCode;
        $data->status = $event->returner->status ?? false;

        $event_data = json_encode($data);
        
        $scannerDetails = $user->scanner_data . $event_data . ';; ';
        
        $user->update([
            'scanner_data' => $scannerDetails,
        ]);

        return back()->with('status', $data);
    }

    public function download($id)
    {
        $data = UserDetail::where('user_id', $id)->value('scanner_data');
        $data_array = explode(';;', $data);
        $csv_data = "id,Email,Telefon,Imie i Nazwisko, ScanedCode \n";

        foreach($data_array as $index => $single){
            if(trim($single) == ""){ continue; }
            
            $single = json_decode($single);

            $csv_data .= $index + 1;
            $csv_data .= ',' . ($single->email ?? ' ');
            $csv_data .= ',' . ($single->phone ?? ' ');
            $csv_data .= ',' . ($single->name ?? ' ');
            $csv_data .= ',' . ($single->qrCode ?? ' ');
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
        
        return back()->with('status', ['message' => 'E-mail sent successfully.']);
    }
}
