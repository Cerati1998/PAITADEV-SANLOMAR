<?php

namespace App\Mail;

use App\Models\Despatch;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DocumentSent extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $client;

    /**
     * Create a new message instance.
     */
    public function __construct($document, $client)
    {
        $this->document = $document;
        $this->client = $client;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $document = strtoupper($this->document->type) . ' ELECTRÓNICA';
        $serie = $this->document->serie;
        $correlativo = str_pad($this->document->correlativo, 6, '0', STR_PAD_LEFT);
        $razonSocial = strtoupper($this->document->company['razonSocial']);

        $subject = "{$document} {$serie}-{$correlativo} | {$razonSocial}";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.document-sent',
            with: [
                'document' => $this->document,
                'client' => $this->client,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $ruc = $this->document->company['ruc'];
        $tipoDoc = $this->document->tipoDoc;
        $serie = $this->document->serie;
        $correlativo = $this->document->correlativo;

        $name = "{$ruc}-{$tipoDoc}-{$serie}-{$correlativo}";

        return [
            Attachment::fromPath(Storage::path($this->document->pdf_path))
                ->as("{$name}.pdf")
                ->withMime('application/pdf'),

            Attachment::fromPath(Storage::path($this->document->xml_path))
                ->as("{$name}.xml")
                ->withMime('text/xml'),

            Attachment::fromPath(Storage::path($this->document->cdr_path))
                ->as("R-{$name}.zip")
                ->withMime('application/zip'),
        ];
    }
}
