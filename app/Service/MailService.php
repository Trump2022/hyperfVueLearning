<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Contract\ConfigInterface;
use RuntimeException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailService
{
    private readonly MailerInterface $mailer;
    private readonly Address $from;

    public function __construct(ConfigInterface $config)
    {
        $dsn = (string) $config->get('mail.dsn', '');
        $fromAddress = (string) $config->get('mail.from_address', '');
        $fromName = (string) $config->get('mail.from_name', 'HyPerf Vue');

        if ($dsn === '' || $fromAddress === '') {
            throw new RuntimeException('Mail configuration is incomplete.');
        }

        $this->mailer = new Mailer(Transport::fromDsn($dsn));
        $this->from = new Address($fromAddress, $fromName);
    }

    public function send(string $to, string $subject, string $text, string $html): void
    {
        $message = (new Email())
            ->from($this->from)
            ->to($to)
            ->subject($subject)
            ->text($text)
            ->html($html);

        $this->mailer->send($message);
    }
}