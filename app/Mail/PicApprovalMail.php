<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PicApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
    ) {}

    public function envelope(): Envelope
    {
        $visitorName = $this->appointment->visitor?->name ?? 'Tamu';

        return new Envelope(
            subject: "Permintaan Kunjungan Baru — {$visitorName}",
        );
    }

    public function content(): Content
    {
        $token = $this->appointment->approval_token;

        return new Content(
            view: 'emails.pic-approval',
            with: [
                'appointment'  => $this->appointment,
                'visitor'      => $this->appointment->visitor,
                'pic'          => $this->appointment->pic,
                'approveUrl'   => url("/appointments/approve/{$token}"),
                'rejectUrl'    => url("/appointments/reject/{$token}"),
            ],
        );
    }
}
