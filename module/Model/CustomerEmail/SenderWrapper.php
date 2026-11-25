<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\CustomerEmail;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as CreditmemoResource;
use Magento\Sales\Model\ResourceModel\Order\Invoice as InvoiceResource;
use Normaro\Verifactu\Model\ResourceModel\CustomerEmail;

/**
 * Wraps a Magento sender call: holds the email, or lets it through and records a manual send.
 */
class SenderWrapper
{
    /**
     * @param Hold $hold
     * @param Gate $gate
     * @param CustomerEmail $customerEmail
     * @param InvoiceResource $invoiceResource
     * @param CreditmemoResource $creditmemoResource
     */
    public function __construct(
        private readonly Hold $hold,
        private readonly Gate $gate,
        private readonly CustomerEmail $customerEmail,
        private readonly InvoiceResource $invoiceResource,
        private readonly CreditmemoResource $creditmemoResource
    ) {
    }

    /**
     * Send now through $send, or hold the email; returns whether Magento sent it.
     *
     * @param Invoice|Creditmemo $document
     * @param callable $send the original sender call
     * @return bool
     */
    public function around(Invoice|Creditmemo $document, callable $send): bool
    {
        $status = $this->hold->decide($document);
        if ($status === null) {
            $sent = (bool)$send();
            if ($sent && $this->gate->isManual()) {
                $this->customerEmail->update(
                    $this->hold->sourceType($document),
                    (int)$document->getId(),
                    CustomerEmail::STATUS_SENT_MANUALLY
                );
            }
            return $sent;
        }
        // Keep Magento's async cron away (it sends rows with send_email = 1 and email_sent null)
        $document->setData('send_email', 0);
        $document->setData('email_sent', null);
        $resource = $document instanceof Creditmemo ? $this->creditmemoResource : $this->invoiceResource;
        $resource->saveAttribute($document, ['send_email', 'email_sent']);
        $this->customerEmail->save(
            $this->hold->sourceType($document),
            (int)$document->getId(),
            (int)$document->getStoreId(),
            $status
        );
        return false;
    }
}
