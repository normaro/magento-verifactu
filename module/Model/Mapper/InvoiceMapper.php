<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\Mapper;

use Magento\Sales\Model\Order\Invoice;
use Normaro\Verifactu\Model\Provider\DocumentData;

/**
 * Magento invoice -> sale document.
 */
class InvoiceMapper
{
    /**
     * @param LineBuilder $lineBuilder
     * @param CustomerBuilder $customerBuilder
     * @param PaymentMethodMapper $paymentMethodMapper
     * @param SimplifiedCustomerPolicy $simplifiedCustomerPolicy
     */
    public function __construct(
        private readonly LineBuilder $lineBuilder,
        private readonly CustomerBuilder $customerBuilder,
        private readonly PaymentMethodMapper $paymentMethodMapper,
        private readonly SimplifiedCustomerPolicy $simplifiedCustomerPolicy
    ) {
    }

    /**
     * Map the invoice; processor ID = invoice increment ID, PO number = order increment ID.
     *
     * @param Invoice $invoice
     * @return DocumentData
     * @throws MappingException
     */
    public function map(Invoice $invoice): DocumentData
    {
        $order = $invoice->getOrder();
        $payment = $order->getPayment();
        $grandTotal = round((float)$invoice->getGrandTotal(), 2);
        $storeId = (int)$order->getStoreId();
        return new DocumentData(
            DocumentData::TRANSACTION_SALE,
            (string)$invoice->getIncrementId(),
            (string)$order->getOrderCurrencyCode(),
            $this->simplifiedCustomerPolicy->apply($this->customerBuilder->build($order), $grandTotal, $storeId),
            $this->lineBuilder->build($invoice, $order),
            $grandTotal,
            $storeId,
            $this->paymentMethodMapper->map($payment ? (string)$payment->getMethod() : ''),
            (string)$order->getIncrementId()
        );
    }
}
