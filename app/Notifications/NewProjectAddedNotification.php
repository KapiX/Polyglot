<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewProjectAddedNotification extends Notification
{
    use Queueable;

    private Project $project;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->canMail(self::class) ? ['mail', 'database'] : ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'new-project-added';
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New project added')
            ->line('New project is available for translation: ' . $this->project->name . '.')
            ->action('Start translating', route('projects.show', $this->project));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'New project added: ' . $this->project->name,
            'project' => $this->project->id
        ];
    }
}
