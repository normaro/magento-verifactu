<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Test\Unit\Model\CustomerEmail;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as CreditmemoResource;
use Magento\Sales\Model\ResourceModel\Order\Invoice as InvoiceResource;
use Normaro\Verifactu\Model\CustomerEmail\Gate;
use Normaro\Verifactu\Model\CustomerEmail\Hold;
use Normaro\Verifactu\Model\CustomerEmail\SenderWrapper;
use Normaro\Verifactu\Model\ResourceModel\CustomerEmail;
use PHPUnit\Framework\TestCase;

/**
 * Held: Magento's sender is not called, the async cron is kept away, the state is recorded.
 */
class SenderWrapperTest extends TestCase
{
    /**
     * A held email.
     *
     * @return void
     */
    public function testHeldEmailIsNotSent(): void
    {
        $hold = $this->createStub(Hold::class);
        $hold->method('decide')->willReturn(CustomerEmail::STATUS_PENDING);
        $hold->method('sourceType')->willReturn('invoice');
        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getId')->willReturn(10);
        $invoice->method('getStoreId')->willReturn(1);
        $set = [];
        $invoice->method('setData')->willReturnCallback(
            function (string $key, $value) use (&$set, $invoice) {
                $set[$key] = $value;
                return $invoice;
            }
        );
        $resource = $this->createMock(InvoiceResource::class);
        $resource->expects($this->once())->method('saveAttribute')->with($invoice, ['send_email', 'email_sent']);
        $state = $this->createMock(CustomerEmail::class);
        $state->expects($this->once())->method('save')->with('invoice', 10, 1, CustomerEmail::STATUS_PENDING);
        $senderWrapper = new SenderWrapper(
            $hold,
            new Gate(),
            $state,
            $resource,
            $this->createStub(CreditmemoResource::class)
        );
        $called = false;
        $sent = $senderWrapper->around($invoice, function () use (&$called) {
            $called = true;
            return true;
        });
        $this->assertFalse($sent);
        $this->assertFalse($called);
        $this->assertSame(['send_email' => 0, 'email_sent' => null], $set);
    }

    /**
     * Not held: Magento sends; a manual send marks a waiting email as sent from the admin.
     *
     * @return void
     */
    public function testManualSendGoesThroughAndIsRecorded(): void
    {
        $hold = $this->createStub(Hold::class);
        $hold->method('decide')->willReturn(null);
        $hold->method('sourceType')->willReturn('invoice');
        $invoice = $this->createStub(Invoice::class);
        $invoice->method('getId')->willReturn(10);
        $state = $this->createMock(CustomerEmail::class);
        $state->expects($this->once())->method('update')->with('invoice', 10, CustomerEmail::STATUS_SENT_MANUALLY);
        $gate = new Gate();
        $gate->openForManualSend();
        $senderWrapper = new SenderWrapper(
            $hold,
            $gate,
            $state,
            $this->createStub(InvoiceResource::class),
            $this->createStub(CreditmemoResource::class)
        );
        $this->assertTrue($senderWrapper->around($invoice, static fn () => true));
    }
}
