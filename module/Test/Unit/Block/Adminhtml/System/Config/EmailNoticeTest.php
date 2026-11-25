<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Test\Unit\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Escaper;
use Normaro\Verifactu\Block\Adminhtml\System\Config\EmailNotice;
use PHPUnit\Framework\TestCase;

class EmailNoticeTest extends TestCase
{
    /**
     * Notice for a customer email mode at default scope.
     *
     * @param string|null $mode
     * @return string
     */
    private function render(?string $mode): string
    {
        $escaper = $this->createStub(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(static fn ($s) => (string)$s);
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn($mode);
        $notice = new EmailNotice(
            $this->createStub(Context::class),
            $escaper,
            $scopeConfig,
            $this->createStub(RequestInterface::class)
        );
        return $notice->render($this->createStub(AbstractElement::class));
    }

    /**
     * As is: a warning message that names the setting to change.
     *
     * @return void
     */
    public function testWarnsAboutMagentoInvoiceEmails(): void
    {
        $html = $this->render(null);
        $this->assertStringContainsString('message-warning', $html);
        $this->assertStringContainsString('Sales Emails (Invoice, Credit Memo)', $html);
        $this->assertStringContainsString('After issue', $html);
    }

    /**
     * After issue: a notice that Sales Emails must stay enabled.
     *
     * @return void
     */
    public function testAfterIssueAsksToKeepSalesEmailsOn(): void
    {
        $html = $this->render('after_issue');
        $this->assertStringContainsString('message-notice', $html);
        $this->assertStringContainsString('Keep Sales Emails', $html);
    }
}
