<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Actions;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\MailSendMode;

/**
 * Setters pick the send mode for you: inline fields switch to inline mode,
 * mailable fields switch to mailable mode.
 */
class SendMailNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'send_mail';
    }

    public function sendMode(MailSendMode|string $mode): static
    {
        return $this->set('send_mode', $mode);
    }

    /**
     * Comma-separated recipients. Supports expressions.
     */
    public function to(string $to): static
    {
        return $this->inline()->set('to', $to);
    }

    public function cc(string $cc): static
    {
        return $this->inline()->set('cc', $cc);
    }

    public function bcc(string $bcc): static
    {
        return $this->inline()->set('bcc', $bcc);
    }

    public function replyTo(string $replyTo): static
    {
        return $this->inline()->set('reply_to', $replyTo);
    }

    public function subject(string $subject): static
    {
        return $this->inline()->set('subject', $subject);
    }

    public function body(string $body): static
    {
        return $this->inline()->set('body', $body);
    }

    public function from(string $from): static
    {
        return $this->inline()->set('from', $from);
    }

    public function isHtml(bool $isHtml = true): static
    {
        return $this->inline()->set('is_html', $isHtml);
    }

    /**
     * @param  array<string, string>  $attachments  Attachment name => path.
     */
    public function attachments(array $attachments): static
    {
        return $this->inline()->set('attachments', $attachments);
    }

    public function attachment(string $name, string $path): static
    {
        return $this->inline()->putEntry('attachments', $name, $path);
    }

    /**
     * @param  class-string  $mailableClass
     */
    public function mailableClass(string $mailableClass): static
    {
        return $this->mailable()->set('mailable_class', $mailableClass);
    }

    public function mailableTo(string $to): static
    {
        return $this->mailable()->set('mailable_to', $to);
    }

    private function inline(): static
    {
        return $this->sendMode(MailSendMode::Inline);
    }

    private function mailable(): static
    {
        return $this->sendMode(MailSendMode::Mailable);
    }
}
