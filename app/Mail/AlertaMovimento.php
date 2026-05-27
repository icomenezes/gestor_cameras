<?php

namespace App\Mail;

use App\Models\Camera;
use App\Models\CameraEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaMovimento extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CameraEvent $event, public Camera $camera) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Alerta: {$this->event->eventLabel()} — {$this->camera->name}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.alerta-movimento');
    }
}
