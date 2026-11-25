<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\CustomerEmail;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Normaro\Verifactu\Model\Mapper\CustomerBuilder;
use Normaro\Verifactu\Model\Mapper\MappingException;
use Normaro\Verifactu\Model\Mapper\SimplifiedCustomerPolicy;

/**
 * Whether the sale goes to Quaderno without the customer's email (simplified invoice).
 *
 * Quaderno cannot email such a document, so in "Quaderno" mode Magento sends it after issue.
 */
class DetachedSale
{
    /**
     * @param CustomerBuilder $customerBuilder
     * @param SimplifiedCustomerPolicy $policy
     */
    public function __construct(
        private readonly CustomerBuilder $customerBuilder,
        private readonly SimplifiedCustomerPolicy $policy
    ) {
    }

    /**
     * True when the invoice (or the invoice a credit memo refunds) is sent without email.
     *
     * @param Invoice|Creditmemo $document
     * @return bool
     */
    public function isDetached(Invoice|Creditmemo $document): bool
    {
        $order = $document->getOrder();
        try {
            $customer = $this->customerBuilder->build($order);
        } catch (MappingException $e) {
            return false;
        }
        $total = (float)$document->getGrandTotal();
        if ($document instanceof Creditmemo) {
            $invoice = $document->getInvoice();
            $total = $invoice ? (float)$invoice->getGrandTotal() : (float)$order->getGrandTotal();
        }
        return $this->policy->apply($customer, round($total, 2), (int)$order->getStoreId())->detached;
    }
}
