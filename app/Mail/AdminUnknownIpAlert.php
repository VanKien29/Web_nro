<?php

namespace App\Mail;

use App\Models\Game\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminUnknownIpAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Account $account,
        public string $newIp,
        public ?string $oldIp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[CẢNH BÁO] Đăng nhập admin từ IP lạ - ' . $this->account->username,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-unknown-ip',
        );
    }
}
