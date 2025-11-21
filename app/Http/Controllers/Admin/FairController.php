<?php

namespace App\Http\Controllers\Admin;

use App\Models\Fair;
use App\Http\Controllers\Controller;
use App\Events\FairCreating;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;

class FairController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // Wyświetlanie listy użytkowników
    public function index()
    {
        $fairs = Fair::all();
        return inertia('Admin/Fairs', [ 
            'fairs' => $fairs,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request)
    {           
        $request->validate([
            'domain' => 'required|string|max:255',
        ]);
        $cleanDomain = preg_replace('/^https?:\/\//', '', $request->domain);

        $event = new FairCreating($cleanDomain);
        event($event);

        $form_meta_id = '';
        foreach($event->jsonData['forms'] as $val){
            $form_meta_id .= $val['form_meta_id'] . ', ';
        }

        $fair_details = Fair::create([
            'fair_meta' => $event->jsonData['meta'] ?? null,
            'domain' => $cleanDomain,
            'fair_name' => $event->jsonData['name'] ?? null,
            'fair_start' => isset($event->jsonData['start']) ? explode(' ', $event->jsonData['start'])[0] : null,
            'fair_end' => isset($event->jsonData['start']) ? explode(' ', $event->jsonData['end'])[0] : null,
            'qr_details' => $form_meta_id ?? null,
        ]);
        
        return redirect()->route('admin.fairs.index')->with('success', 'Targi dodane!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
