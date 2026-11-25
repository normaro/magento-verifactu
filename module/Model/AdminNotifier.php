<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Notification\NotifierInterface;

/**
 * Admin notifications for documents that need the merchant (blocked, draft, rejected, failed).
 * "Read Details" opens the Magento invoice / credit memo (through verifactu/document/open).
 */
class AdminNotifier
{
    /**
     * @param NotifierInterface $notifier
     * @param UrlInterface $backendUrl
     * @param Config $config
     */
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly UrlInterface $backendUrl,
        private readonly Config $config
    ) {
    }

    /**
     * Raise a major notification about a row.
     *
     * @param Document $document
     * @return void
     */
    public function notify(Document $document): void
    {
        $status = (string)$document->getData('status');
        $statusLabel = Document::STATUS_LABELS[$status] ?? $status;
        $title = $status === Document::STATUS_BLOCKED
            ? (string)__('Verifactu: %1 not sent (blocked)', $document->getMagentoLabel())
            : (string)__('Verifactu: %1 is %2', $document->getMagentoLabel(), __($statusLabel));
        $text = (string)($document->getData('last_error') ?: __('See Sales > Operations > Verifactu Documents.'));
        $email = $this->emailNote($document);
        $this->notifier->addMajor(
            $title,
            $email === '' ? $text : $text . ' ' . $email,
            $this->openUrl($document)
        );
    }

    /**
     * The held customer email could not be sent after issue.
     *
     * @param Document $document
     * @param string $reason
     * @return void
     */
    public function notifyEmailFailed(Document $document, string $reason): void
    {
        $this->notifier->addMajor(
            (string)__('Verifactu: customer email of %1 not sent', $document->getMagentoLabel()),
            (string)__(
                '%1 Send it with "Send Email" on the Magento page.',
                $reason
            ),
            $this->openUrl($document)
        );
    }

    /**
     * Sales Emails are off while the customer email mode needs them.
     *
     * @param string $text
     * @return void
     */
    public function notifySalesEmailsOff(string $text): void
    {
        $this->notifier->addMajor((string)__('Verifactu: Magento emails are switched off'), $text);
    }

    /**
     * Note on the customer email when emails wait for the Verifactu invoice ("After issue" / "Quaderno").
     *
     * @param Document $document
     * @return string
     */
    private function emailNote(Document $document): string
    {
        if ($this->config->getCustomerEmailMode((int)$document->getData('store_id')) === Config::EMAIL_AS_IS) {
            return '';
        }
        if ((string)$document->getData('status') === Document::STATUS_BLOCKED
            && (int)$document->getData('block_permanent') === 1
        ) {
            return (string)__(
                'The customer email will not be sent automatically: create the document in Quaderno, '
                . 'then use "Send Email" on the Magento page.'
            );
        }
        return (string)__('The customer email waits until the Verifactu invoice is issued.');
    }

    /**
     * Admin URL that opens the Magento invoice / credit memo of the row.
     *
     * @param Document $document
     * @return string
     */
    private function openUrl(Document $document): string
    {
        return $this->backendUrl->getUrl('verifactu/document/open', ['id' => $document->getId(), '_nosecret' => true]);
    }
}
