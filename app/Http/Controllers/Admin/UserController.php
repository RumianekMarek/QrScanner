<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Fair;
use App\Models\UserDetail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\LoginToken;

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

        $user = UserDetail::findOrFail($request->user_id);
        $user->update($request->only(['fair_meta', 'phone', 'company_name', 'placement']));

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
}