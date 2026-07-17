<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IdentifiantsUtilisateurMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $utilisateur,
        public string $motDePasse,
        public bool $nouveauCompte,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->nouveauCompte
                ? 'Votre compte G-Stock Vaisselle'
                : 'Votre mot de passe G-Stock Vaisselle a été réinitialisé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.identifiants-utilisateur',
        );
    }
}
