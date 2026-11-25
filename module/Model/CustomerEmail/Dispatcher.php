<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\CustomerEmail;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Normaro\Verifactu\Model\AdminNotifier;
use Normaro\Verifactu\Model\Document;
use Normaro\Verifactu\Model\ResourceModel\CustomerEmail;

/**
 * Sends the held Magento email once the Verifactu document is issued (consumer / cron, not a customer request).
 */
class Dispatcher
{
    /**
     * @param CustomerEmail $customerEmail
     * @param Gate $gate
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param InvoiceSender $invoiceSender
     * @param CreditmemoSender $creditmemoSender
     * @param AdminNotifier $notifier
     * @param State $appState
     */
    public function __construct(
        private readonly CustomerEmail $customerEmail,
        private readonly Gate $gate,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly CreditmemoRepositoryInterface $creditmemoRepository,
        private readonly InvoiceSender $invoiceSender,
        private readonly CreditmemoSender $creditmemoSender,
        private readonly AdminNotifier $notifier,
        private readonly State $appState
    ) {
    }

    /**
     * Send the email of an issued row if it is waiting.
     *
     * @param Document $row
     * @return void
     */
    public function afterIssued(Document $row): void
    {
        $type = (string)$row->getData('source_type');
        $sourceId = (int)$row->getData('source_id');
        if ($this->customerEmail->getStatus($type, $sourceId) !== CustomerEmail::STATUS_PENDING) {
            return;
        }
        try {
            $sent = (bool)$this->gate->through(fn () => $this->inArea(fn () => $this->send($type, $sourceId)));
            $message = $sent ? null
                : (string)__('Magento did not send the email: check Sales Emails > Invoice / Credit Memo > Enabled.');
        } catch (\Throwable $e) {
            $sent = false;
            $message = $e->getMessage();
        }
        $this->customerEmail->update(
            $type,
            $sourceId,
            $sent ? CustomerEmail::STATUS_SENT : CustomerEmail::STATUS_FAILED,
            $message
        );
        if (!$sent) {
            $this->notifier->notifyEmailFailed($row, (string)$message);
        }
    }

    /**
     * Run in the current area, or in "crontab" (as Magento's own async sending) when none is set (queue consumer).
     *
     * @param callable $callback
     * @return mixed
     */
    private function inArea(callable $callback): mixed
    {
        try {
            $this->appState->getAreaCode();
        } catch (LocalizedException $e) {
            return $this->appState->emulateAreaCode(Area::AREA_CRONTAB, $callback);
        }
        return $callback();
    }

    /**
     * Load the Magento document and send its email synchronously.
     *
     * @param string $type
     * @param int $sourceId
     * @return bool
     */
    private function send(string $type, int $sourceId): bool
    {
        if ($type === Document::SOURCE_CREDITMEMO) {
            $creditmemo = $this->creditmemoRepository->get($sourceId);
            return $creditmemo instanceof Creditmemo && $this->creditmemoSender->send($creditmemo, true);
        }
        $invoice = $this->invoiceRepository->get($sourceId);
        return $invoice instanceof Invoice && $this->invoiceSender->send($invoice, true);
    }
}
