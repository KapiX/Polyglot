<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class AppSettingsController extends Controller
{
    public function index()
    {
        $used_exts = ['zip'];
        $available_exts = array_intersect(get_loaded_extensions(), $used_exts);
        return view('settings')
            ->with('php', PHP_VERSION)
            ->with('used_exts', $used_exts)
            ->with('available_exts', $available_exts);
    }

    public function testMail(Request $request) {
        $mail = new Mailable();
        $mail->to($request->input('email'));
        $mail->subject('Polyglot test mail');
        $mail->with('slot', 'Polyglot test mail');
        $mail->markdown('mail::message');
        Mail::send($mail);
        return redirect(route('settings.index'));
    }
}
