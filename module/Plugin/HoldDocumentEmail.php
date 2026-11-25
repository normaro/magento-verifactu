<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Plugin;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Normaro\Verifactu\Model\CustomerEmail\SenderWrapper;

/**
 * InvoiceSender::send() / CreditmemoSender::send(): hold the email until the Verifactu invoice is issued.
 */
class HoldDocumentEmail
{
    /**
     * @param SenderWrapper $senderWrapper
     */
    public function __construct(private readonly SenderWrapper $senderWrapper)
    {
    }

    /**
     * Send now or hold.
     *
     * @param object $subject InvoiceSender or CreditmemoSender
     * @param callable $proceed
     * @param mixed $document
     * @param bool $forceSyncMode
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) Magento's own signature
     */
    public function aroundSend(object $subject, callable $proceed, $document, $forceSyncMode = false)
    {
        if (!$document instanceof Invoice && !$document instanceof Creditmemo) {
            return $proceed($document, $forceSyncMode);
        }
        return $this->senderWrapper->around($document, static fn () => $proceed($document, $forceSyncMode));
    }
}
