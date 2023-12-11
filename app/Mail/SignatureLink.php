<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SignatureLink extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($signatureToken)
    {
        $this->link = route("get-signature-input", [
            "signatureToken" => $signatureToken
        ]);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(__('Ihr Link zur Unterschrift'))->view("emails.signature-link")
            ->with(["link" => $this->link]);
    }
}
