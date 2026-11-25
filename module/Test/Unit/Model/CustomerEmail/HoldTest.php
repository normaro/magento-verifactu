<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Test\Unit\Model\CustomerEmail;

use Magento\Sales\Model\Order\Invoice;
use Normaro\Verifactu\Model\Config;
use Normaro\Verifactu\Model\CustomerEmail\DetachedSale;
use Normaro\Verifactu\Model\CustomerEmail\Gate;
use Normaro\Verifactu\Model\CustomerEmail\Hold;
use Normaro\Verifactu\Model\Document;
use Normaro\Verifactu\Model\DocumentRepository;
use Normaro\Verifactu\Model\ResourceModel\CustomerEmail;
use PHPUnit\Framework\TestCase;

/**
 * When Magento's email waits for the Verifactu invoice.
 */
class HoldTest extends TestCase
{
    /**
     * Hold for a module state.
     *
     * @param array{enabled?:bool,mode?:string,status?:string|null,detached?:bool,gate?:bool} $state
     * @return Hold
     */
    private function hold(array $state): Hold
    {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($state['enabled'] ?? true);
        $config->method('getCustomerEmailMode')->willReturn($state['mode'] ?? Config::EMAIL_AFTER_ISSUE);
        $repository = $this->createStub(DocumentRepository::class);
        $status = $state['status'] ?? null;
        $row = null;
        if ($status !== null) {
            $row = $this->createStub(Document::class);
            $row->method('getData')->willReturnMap([['status', null, $status]]);
        }
        $repository->method('findBySource')->willReturn($row);
        $detached = $this->createStub(DetachedSale::class);
        $detached->method('isDetached')->willReturn($state['detached'] ?? false);
        $gate = new Gate();
        if ($state['gate'] ?? false) {
            $gate->openForManualSend();
        }
        return new Hold($config, $gate, $repository, $detached);
    }

    /**
     * An invoice of store 1.
     *
     * @return Invoice
     */
    private function invoice(): Invoice
    {
        $invoice = $this->createStub(Invoice::class);
        $invoice->method('getStoreId')->willReturn(1);
        $invoice->method('getId')->willReturn(10);
        return $invoice;
    }

    /**
     * After issue, not issued yet: held as pending.
     *
     * @return void
     */
    public function testAfterIssueHoldsUntilIssued(): void
    {
        $this->assertSame(CustomerEmail::STATUS_PENDING, $this->hold(['status' => 'queued'])->decide($this->invoice()));
        $this->assertSame(CustomerEmail::STATUS_PENDING, $this->hold([])->decide($this->invoice()));
    }

    /**
     * After issue, already issued: Magento sends now.
     *
     * @return void
     */
    public function testAfterIssueSendsWhenIssued(): void
    {
        $this->assertNull($this->hold(['status' => Document::STATUS_ISSUED])->decide($this->invoice()));
        $this->assertNull($this->hold(['status' => Document::STATUS_ISSUED_WITH_WARNING])->decide($this->invoice()));
    }

    /**
     * Out of scope (module disabled for the store), As is mode, open gate: Magento sends now.
     *
     * @return void
     */
    public function testNoHoldOutOfScopeAsIsOrOpenGate(): void
    {
        $this->assertNull($this->hold(['enabled' => false])->decide($this->invoice()));
        $this->assertNull($this->hold(['mode' => Config::EMAIL_AS_IS])->decide($this->invoice()));
        $this->assertNull($this->hold(['gate' => true])->decide($this->invoice()));
    }

    /**
     * Quaderno mode: Quaderno sends, except a sale sent without the customer's email.
     *
     * @return void
     */
    public function testQuadernoModeFallsBackForDetachedSales(): void
    {
        $this->assertSame(
            CustomerEmail::STATUS_QUADERNO,
            $this->hold(['mode' => Config::EMAIL_QUADERNO])->decide($this->invoice())
        );
        $this->assertSame(
            CustomerEmail::STATUS_PENDING,
            $this->hold(['mode' => Config::EMAIL_QUADERNO, 'detached' => true])->decide($this->invoice())
        );
    }
}
