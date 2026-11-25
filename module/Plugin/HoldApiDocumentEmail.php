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
 * Invoice\Sender\EmailSender / Creditmemo\Sender\EmailSender (service API): same hold as the admin senders.
 */
class HoldApiDocumentEmail
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
     * @param object $subject
     * @param callable $proceed
     * @param mixed $order
     * @param mixed $document
     * @param mixed $comment
     * @param bool $forceSyncMode
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) Magento's own signature
     */
    public function aroundSend(
        object $subject,
        callable $proceed,
        $order,
        $document,
        $comment = null,
        $forceSyncMode = false
    ) {
        if (!$document instanceof Invoice && !$document instanceof Creditmemo) {
            return $proceed($order, $document, $comment, $forceSyncMode);
        }
        return $this->senderWrapper->around(
            $document,
            static fn () => $proceed($order, $document, $comment, $forceSyncMode)
        );
    }
}
