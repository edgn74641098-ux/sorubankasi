<?php

namespace App\Notifications;

use App\Models\QuestionReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestionReportAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly QuestionReport $report,
        private readonly ?string $oldCorrectOption
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $suggested = $this->report->suggested_correct_option;

        return (new MailMessage)
            ->subject('Soru itiraziniz kabul edildi')
            ->greeting('Merhaba ' . $notifiable->name)
            ->line('Soru itiraziniz incelendi ve kabul edildi. Katkiniz icin tesekkur ederiz.')
            ->line($suggested
                ? "Sorunun dogru cevabi {$this->oldCorrectOption} sikki yerine {$suggested} olarak guncellendi."
                : 'Bildirdiginiz sorun moderator ekibi tarafindan onaylandi.')
            ->line($this->report->user_message ?: 'Dikkatiniz ve katkilariniz icin tesekkur ederiz.');
    }
}
