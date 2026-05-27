<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssinaturaVencendo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Subscription $subscription,
        public int $diasRestantes,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->diasRestantes === 1 ? 'amanhã' : "em {$this->diasRestantes} dias";

        return new Envelope(subject: "Sua assinatura vence {$label}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.assinatura-vencendo');
    }
}
