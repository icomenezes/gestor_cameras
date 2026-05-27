<?php

namespace App\Mail;

use App\Models\Camera;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AcessoCamerasConcedido extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Camera $camera) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Acesso liberado: {$this->camera->name}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.acesso-camera-concedido');
    }
}
