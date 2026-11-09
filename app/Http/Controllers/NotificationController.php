<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->limit(50)->get(),
        ]);
    }

    public function toutLire(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('succes', 'Notifications marquées comme lues.');
    }
}