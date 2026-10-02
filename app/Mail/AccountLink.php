<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AccountLink extends Mailable
{
    public function __construct(public string $title, public string $link) {}

    public function build(): static
    {
        return $this->subject($this->title)->text('mail.link');
    }
}
