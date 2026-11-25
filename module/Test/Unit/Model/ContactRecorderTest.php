<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Test\Unit\Model;

use Magento\Sales\Model\Order;
use Normaro\Verifactu\Model\Config;
use Normaro\Verifactu\Model\ContactRecorder;
use Normaro\Verifactu\Model\Provider\CustomerData;
use Normaro\Verifactu\Model\Provider\DocumentData;
use Normaro\Verifactu\Model\Provider\ProviderResult;
use Normaro\Verifactu\Model\ResourceModel\CustomerContact;
use PHPUnit\Framework\TestCase;

/**
 * The provider contact of a registered customer is remembered, unless the sale was sent detached.
 */
class ContactRecorderTest extends TestCase
{
    /**
     * Registered customer's order.
     *
     * @return Order
     */
    private function order(): Order
    {
        $order = $this->createStub(Order::class);
        $order->method('getCustomerId')->willReturn(7);
        $order->method('getCustomerIsGuest')->willReturn(false);
        $order->method('getStoreId')->willReturn(1);
        return $order;
    }

    /**
     * Spanish person with email, optionally with a stored provider contact.
     *
     * @param string|null $contactId
     * @return CustomerData
     */
    private function customer(?string $contactId = null): CustomerData
    {
        return new CustomerData(
            CustomerData::KIND_PERSON,
            'Ana',
            'Prueba',
            'ES',
            null,
            null,
            null,
            null,
            null,
            null,
            'ana@example.com',
            $contactId
        );
    }

    /**
     * Sale document for the given customer.
     *
     * @param CustomerData $customer
     * @return DocumentData
     */
    private function document(CustomerData $customer): DocumentData
    {
        return new DocumentData(
            DocumentData::TRANSACTION_SALE,
            '000000001',
            'EUR',
            $customer,
            [],
            29.04,
            1,
            'credit_card'
        );
    }

    /**
     * Provider result with a new contact.
     *
     * @return ProviderResult
     */
    private function providerResult(): ProviderResult
    {
        return new ProviderResult(providerId: '637456', type: 'invoice', status: 'issued', contactId: '9926740');
    }

    /**
     * First sale of a registered customer: the new contact is stored.
     *
     * @return void
     */
    public function testNewContactIsStored(): void
    {
        $contacts = $this->createMock(CustomerContact::class);
        $contacts->expects($this->once())->method('save')->with(7, 'sandbox', '9926740');
        $config = $this->createStub(Config::class);
        $config->method('getEnvironment')->willReturn('sandbox');
        $customer = $this->customer();
        (new ContactRecorder($contacts, $config))
            ->record($this->order(), $this->document($customer), $this->providerResult());
    }

    /**
     * A detached sale (simplified invoice, no email) must not become the customer's contact.
     *
     * @return void
     */
    public function testDetachedSaleIsNotStored(): void
    {
        $contacts = $this->createMock(CustomerContact::class);
        $contacts->expects($this->never())->method('save');
        $config = $this->createStub(Config::class);
        $config->method('getEnvironment')->willReturn('sandbox');
        $customer = $this->customer('9795220')->detached();
        (new ContactRecorder($contacts, $config))
            ->record($this->order(), $this->document($customer), $this->providerResult());
    }
}
