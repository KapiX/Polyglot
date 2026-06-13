<?php

namespace App\Notifications;

use App\Models\Language;
use App\Models\Project;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TranslationCompletedNotification extends Notification
{
    private $project;
    private $language;

    public function __construct(Project $project, Language $language)
    {
        $this->project = $project;
        $this->language = $language;
    }

    public function via($notifiable): array
    {
        return $notifiable->canMail(self::class) ? ['mail', 'database'] : ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'translation-completed';
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->project->name . ': ' . $this->language->name . ' translation completed')
            ->line($this->language->name . ' translation of ' . $this->project->name . ' has been completed.')
            ->action('View project', route('projects.show', $this->project));
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => $this->project->name . ': ' . $this->language->name . ' translation completed',
            'project' => $this->project->id, 'language' => $this->language->id];
    }
}
