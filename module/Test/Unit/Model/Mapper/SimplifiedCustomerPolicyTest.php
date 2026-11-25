<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Test\Unit\Model\Mapper;

use Normaro\Verifactu\Model\Config;
use Normaro\Verifactu\Model\Mapper\SimplifiedCustomerPolicy;
use Normaro\Verifactu\Model\Provider\CustomerData;
use PHPUnit\Framework\TestCase;

/**
 * A Spanish sale without NIF below the limit goes without email and stored contact (simplified invoice).
 */
class SimplifiedCustomerPolicyTest extends TestCase
{
    /**
     * Policy with the given "pass email" setting and a 3000 EUR limit.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) test helper: the flag is the config value under test
     *
     * @param bool $passEmail
     * @return SimplifiedCustomerPolicy
     */
    private function policy(bool $passEmail = false): SimplifiedCustomerPolicy
    {
        $config = $this->createStub(Config::class);
        $config->method('getThreshold')->willReturn(3000.0);
        $config->method('isSimplifiedEmailPassed')->willReturn($passEmail);
        return new SimplifiedCustomerPolicy($config);
    }

    /**
     * Customer with email and a stored provider contact.
     *
     * @param string $country
     * @param string|null $taxId
     * @return CustomerData
     */
    private function customer(string $country = 'ES', ?string $taxId = null): CustomerData
    {
        return new CustomerData(
            CustomerData::KIND_PERSON,
            'Ana',
            'Prueba',
            $country,
            $taxId,
            'Calle Mayor 1',
            null,
            'Alicante',
            '03001',
            null,
            'ana@example.com',
            '9795220'
        );
    }

    /**
     * Below the limit, Spanish, no NIF: email and contact are dropped, the rest is kept.
     *
     * @return void
     */
    public function testSpanishSaleWithoutNifBelowTheLimitIsDetached(): void
    {
        $result = $this->policy()->apply($this->customer(), 29.04, 1);
        $this->assertTrue($result->detached);
        $this->assertNull($result->email);
        $this->assertNull($result->contactId);
        $this->assertSame('Ana', $result->firstName);
        $this->assertSame('Calle Mayor 1', $result->street1);
        $this->assertSame('03001', $result->postcode);
    }

    /**
     * From the limit on the sale needs a full invoice (the precheck asks for a NIF): unchanged.
     *
     * @return void
     */
    public function testAtTheLimitTheCustomerIsUnchanged(): void
    {
        $customer = $this->customer();
        $this->assertSame($customer, $this->policy()->apply($customer, 3000.0, 1));
    }

    /**
     * With a NIF the sale is a full invoice: unchanged.
     *
     * @return void
     */
    public function testCustomerWithNifIsUnchanged(): void
    {
        $customer = $this->customer('ES', '12345678Z');
        $this->assertSame($customer, $this->policy()->apply($customer, 29.04, 1));
    }

    /**
     * Outside Spain: unchanged.
     *
     * @return void
     */
    public function testForeignCustomerIsUnchanged(): void
    {
        $customer = $this->customer('DE');
        $this->assertSame($customer, $this->policy()->apply($customer, 29.04, 1));
    }

    /**
     * The merchant chose to pass the email: unchanged.
     *
     * @return void
     */
    public function testEmailIsPassedWhenConfigured(): void
    {
        $customer = $this->customer();
        $this->assertSame($customer, $this->policy(true)->apply($customer, 29.04, 1));
    }
}
