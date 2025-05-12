<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Proyek;

class BudgetExceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $proyek;
    protected $message;

    public function __construct(Proyek $proyek, $message)
    {
        $this->proyek = $proyek;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => $this->message,
            'proyek_id' => $this->proyek->id,
            'proyek_name' => $this->proyek->nama_proyek,
            'type' => 'budget_exceeded'
        ];
    }
} 