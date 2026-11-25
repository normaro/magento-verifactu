<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Test\Unit\Model\CustomerEmail;

use Magento\Framework\App\State;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Normaro\Verifactu\Model\AdminNotifier;
use Normaro\Verifactu\Model\CustomerEmail\Dispatcher;
use Normaro\Verifactu\Model\CustomerEmail\Gate;
use Normaro\Verifactu\Model\Document;
use Normaro\Verifactu\Model\ResourceModel\CustomerEmail;
use PHPUnit\Framework\TestCase;

/**
 * After issue: a waiting email is sent once, with the gate open; a failure is recorded and notified.
 */
class DispatcherTest extends TestCase
{
    /**
     * Issued row of invoice 10.
     *
     * @return Document
     */
    private function row(): Document
    {
        $row = $this->createStub(Document::class);
        $row->method('getData')->willReturnMap([['source_type', null, 'invoice'], ['source_id', null, 10]]);
        return $row;
    }

    /**
     * Dispatcher with the given email state and sender result.
     *
     * @param string|null $status
     * @param bool $sent
     * @param (CustomerEmail&\PHPUnit\Framework\MockObject\Stub)|null $state
     * @param AdminNotifier|null $notifier
     * @param (InvoiceSender&\PHPUnit\Framework\MockObject\Stub)|null $sender
     * @return Dispatcher
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) test helper: the flag is the sender result under test
     */
    private function dispatcher(
        ?string $status,
        bool $sent,
        ?CustomerEmail $state = null,
        ?AdminNotifier $notifier = null,
        ?InvoiceSender $sender = null
    ): Dispatcher {
        if ($state === null) {
            $state = $this->createStub(CustomerEmail::class);
        }
        $state->method('getStatus')->willReturn($status);
        $invoices = $this->createStub(InvoiceRepositoryInterface::class);
        $invoices->method('get')->willReturn($this->createStub(Invoice::class));
        if ($sender === null) {
            $sender = $this->createStub(InvoiceSender::class);
            $sender->method('send')->willReturn($sent);
        }
        $appState = $this->createStub(State::class);
        $appState->method('getAreaCode')->willReturn('crontab');
        return new Dispatcher(
            $state,
            new Gate(),
            $invoices,
            $this->createStub(CreditmemoRepositoryInterface::class),
            $sender,
            $this->createStub(CreditmemoSender::class),
            $notifier ?? $this->createStub(AdminNotifier::class),
            $appState
        );
    }

    /**
     * Pending → sent.
     *
     * @return void
     */
    public function testPendingEmailIsSent(): void
    {
        $state = $this->createMock(CustomerEmail::class);
        $state->expects($this->once())->method('update')->with('invoice', 10, CustomerEmail::STATUS_SENT, null);
        $this->dispatcher(CustomerEmail::STATUS_PENDING, true, $state)->afterIssued($this->row());
    }

    /**
     * Pending, Magento did not send → failed + admin notification.
     *
     * @return void
     */
    public function testNotSentIsFailedAndNotified(): void
    {
        $state = $this->createMock(CustomerEmail::class);
        $state->expects($this->once())->method('update')
            ->with('invoice', 10, CustomerEmail::STATUS_FAILED, $this->stringContains('Sales Emails'));
        $notifier = $this->createMock(AdminNotifier::class);
        $notifier->expects($this->once())->method('notifyEmailFailed');
        $this->dispatcher(CustomerEmail::STATUS_PENDING, false, $state, $notifier)->afterIssued($this->row());
    }

    /**
     * Nothing waiting (As is, Quaderno, already sent): no send.
     *
     * @return void
     */
    public function testNothingWaitingSendsNothing(): void
    {
        $sender = $this->createMock(InvoiceSender::class);
        $sender->expects($this->never())->method('send');
        foreach ([null, CustomerEmail::STATUS_SENT, CustomerEmail::STATUS_QUADERNO] as $status) {
            $this->dispatcher($status, true, null, null, $sender)->afterIssued($this->row());
        }
    }
}
