<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// The 6-digit "forgot password" code (see App\Services\PasswordResetService).
// Mail only, in Arabic with an English line, since we don't know which
// language the person uses.
class PasswordResetCode extends Notification
{
    use Queueable;

    public function __construct(public string $code, public int $expiresMinutes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Semsar — رمز إعادة تعيين كلمة المرور: {$this->code}")
            ->greeting("مرحبًا {$notifiable->name}،")
            ->line('رمز إعادة تعيين كلمة المرور الخاص بك هو:')
            ->line("**{$this->code}**")
            ->line("الرمز صالح لمدة {$this->expiresMinutes} دقيقة. إذا لم تطلب ذلك، تجاهل هذه الرسالة — كلمة مرورك لم تتغير.")
            ->line("Your Semsar password reset code is {$this->code}. It expires in {$this->expiresMinutes} minutes.")
            ->salutation('Semsar');
    }
}
