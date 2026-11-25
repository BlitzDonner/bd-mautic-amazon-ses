<?php

namespace MauticPlugin\AmazonSESBundle\Services\AmazonSES;

/**
 * B&D 24.11.2026: eigenständig, ohne teknasyon/aws-ses-notification (installierte Fassung kennt Email\ComplaintEmail nicht).
 */
class ComplaintEmail
{
    protected array $sesMessage;
    protected string $complaintFeedbackType;

    public function __construct(array $sesMessage)
    {
        $this->sesMessage            = $sesMessage;
        $this->complaintFeedbackType = $sesMessage['complaint']['complaintFeedbackType'] ?? '';
    }

    /** @return array<string> E-Mail-Adressen der Beschwerdeführenden */
    public function getReceipts(): array
    {
        $adressen = [];
        foreach ($this->sesMessage['complaint']['complainedRecipients'] ?? [] as $recipient) {
            if (!empty($recipient['emailAddress'])) {
                $adressen[] = $recipient['emailAddress'];
            }
        }

        return $adressen;
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

    public function getComplaintFeedbackType(): string
    {
        return $this->complaintFeedbackType;
    }
}
