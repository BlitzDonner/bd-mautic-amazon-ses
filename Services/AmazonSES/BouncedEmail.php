<?php

namespace MauticPlugin\AmazonSESBundle\Services\AmazonSES;

/**
 * B&D 24.11.2026: eigenständig, ohne teknasyon/aws-ses-notification (installierte Fassung kennt Email\BouncedEmail nicht).
 * Liest das SES-Ereignis (Event Publishing, Raw Message Delivery).
 */
class BouncedEmail
{
    protected array $sesMessage;
    protected array $bounce;

    public function __construct(array $sesMessage)
    {
        $this->sesMessage = $sesMessage;
        $this->bounce     = $sesMessage['bounce'] ?? [];
    }

    /** Nur dauerhafte Rückläufer sperren. */
    public function shouldRemoved(): bool
    {
        return 'Permanent' === ($this->bounce['bounceType'] ?? '');
    }

    public function getHeaders(string $name): ?string
    {
        foreach ($this->sesMessage['mail']['headers'] ?? [] as $header) {
            if (0 === strcasecmp($header['name'] ?? '', $name)) {
                return $header['value'] ?? null;
            }
        }

        return null;
    }

    /** @return array<BouncedEmailRecipient> */
    public function getRecipientDetails(): array
    {
        $receipts = [];
        foreach ($this->bounce['bouncedRecipients'] ?? [] as $bouncedRecipient) {
            $receipts[] = new BouncedEmailRecipient($bouncedRecipient);
        }

        return $receipts;
    }

    public function getBounceSubType(): string
    {
        return $this->bounce['bounceSubType'] ?? '';
    }

    public function getBounce(): array
    {
        return $this->bounce;
    }
}
