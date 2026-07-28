<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PicWalkinNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
    ) {}

    public function envelope(): Envelope
    {
        $visitorName = $this->appointment->visitor?->name ?? 'Tamu';

        return new Envelope(
            subject: "Tamu Walk-In Telah Check-In — {$visitorName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pic-walkin-notification',
            with: [
                'appointment' => $this->appointment,
                'visitor'     => $this->appointment->visitor,
                'pic'         => $this->appointment->pic,
            ],
        );
    }
}
