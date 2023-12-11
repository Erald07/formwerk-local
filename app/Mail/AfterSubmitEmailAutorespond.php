<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AfterSubmitEmailAutorespond extends Mailable
{
    use Queueable, SerializesModels;
    private $message, $fields;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($options)
    {
        $this->subject = $options["subject"];
        $this->message = $options["message"];
        $this->fields = $options["fields"];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        foreach ($this->fields as $id => $value) {
            $pattern = "/{{" . $id . "\|([^}])*}}/";
            $this->message = preg_replace(
                $pattern,
                is_array($value) ? implode(",", $value) : (is_object($value) ? json_encode($value) : $value),
                $this->message
            );
        }

        return $this->view("emails.form-submitted-autorespond")
            ->subject($this->subject)
            ->with([ "content" => $this->message ]);
    }
}

