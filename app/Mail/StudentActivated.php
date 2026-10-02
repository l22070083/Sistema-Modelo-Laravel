<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class StudentActivated extends Mailable
{
    public function __construct(public string $nombre) {}

    public function build(): static
    {
        return $this->subject('Tu cuenta ha sido dada de alta')->text('mail.alta');
    }
}
