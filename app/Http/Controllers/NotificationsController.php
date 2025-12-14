<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(20);
        return view('notifications.index')->with('notifications', $notifications);
    }

    public function show($id)
    {
        $notif = Auth::user()->notifications()->findOrFail($id);
        $notif->markAsRead();
        $redirect_url = '/';
        if($notif->type == 'project-file-updated' || $notif->type == 'translation-completed')
        {
            $project_id = $notif->data['project'];
            $project = Project::where('id', $project_id)->get()[0];
            $redirect_url = route('projects.show', $project);
        }
        return redirect($redirect_url);
    }

    public function destroy($id)
    {
        $notif = Auth::user()->notifications()->findOrFail($id);
        $notif->delete();
        return redirect()->back();
    }

    public function destroyAll()
    {
        Auth::user()->notifications()->delete();
        return redirect()->back();
    }

    // mark as read
    public function update($id)
    {
        $notif = Auth::user()->notifications()->findOrFail($id);
        $notif->markAsRead();
        return redirect()->back();
    }

    // mark all as read for current user
    public function updateAll()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return redirect()->back();
    }
}
