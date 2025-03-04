<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;

    public function __construct($resetUrl)
    {
        $this->url = $resetUrl;
    }

    public function build()
    {
        return $this->markdown('emails.reset-password')
                    ->subject('Reset Password - Kos SidoRame12');
    }
} 