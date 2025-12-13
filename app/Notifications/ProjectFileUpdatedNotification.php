<?php

namespace App\Notifications;

use App\Models\File;
use App\Models\Project;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectFileUpdatedNotification extends Notification
{
    private Project $project;
    private File $file;

    public function __construct(Project $project, File $file)
    {
        $this->project = $project;
        $this->file = $file;
    }

    public function via($notifiable): array
    {
        // TODO: return $notifiable->email ? ['mail', 'database'] : ['database'];
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'project-file-updated';
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->project->name . ' texts updated')
            ->line($this->project->name . ' texts in file ' . $this->file->name . ' have been updated.')
            ->action('Translate updates', route('projects.show', $this->project));
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => $this->project->name . ' texts updated',
            'file' => $this->file->id,
            'project' => $this->project->id];
    }
}
