<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\Mapper;

use Normaro\Verifactu\Model\Config;
use Normaro\Verifactu\Model\Provider\CustomerData;

/**
 * Customer of a sale that must become a simplified invoice: sent without email and stored contact.
 *
 * Quaderno issues a full invoice instead of a simplified invoice (receipt) when the customer's
 * contact has an email, and the AEAT accepts that invoice without the customer's NIF with error
 * 2006 (Quaderno sandbox, 2026-11-25). For a Spanish sale without NIF below the simplified
 * invoice limit the customer is therefore detached, unless the merchant chose to pass the email.
 */
class SimplifiedCustomerPolicy
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * The customer as sent for this sale.
     *
     * @param CustomerData $customer
     * @param float $grandTotal sale total incl. IVA
     * @param int $storeId
     * @return CustomerData
     */
    public function apply(CustomerData $customer, float $grandTotal, int $storeId): CustomerData
    {
        if ($customer->country !== 'ES'
            || ($customer->taxId !== null && $customer->taxId !== '')
            || $grandTotal >= $this->config->getThreshold($storeId)
            || $this->config->isSimplifiedEmailPassed($storeId)
        ) {
            return $customer;
        }
        return $customer->detached();
    }
}
