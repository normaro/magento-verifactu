<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Block\Adminhtml\System\Config;

use Magento\Backend\Block\AbstractBlock;
use Magento\Backend\Block\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Store\Model\ScopeInterface;
use Normaro\Verifactu\Model\Config;

/**
 * Warning at the top of the Verifactu config section: Magento's own invoice / credit memo emails.
 */
class EmailNotice extends AbstractBlock implements RendererInterface
{
    /**
     * @param Context $context
     * @param Escaper $escaper
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     * @param mixed[] $data
     */
    public function __construct(
        Context $context,
        private readonly Escaper $escaper,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function render(AbstractElement $element)
    {
        $mode = $this->mode();
        if ($mode === Config::EMAIL_AFTER_ISSUE) {
            $text = (string)__(
                'Customer emails: After issue. Magento\'s invoice and credit memo emails wait until Quaderno has'
                . ' issued the Verifactu invoice and the AEAT has registered it (at most 15 minutes), then go out'
                . ' with its number, PDF and AEAT link. Keep Sales Emails > Invoice and Credit Memo enabled.'
            );
            return '<div class="message message-notice verifactu-email-notice">'
                . $this->escaper->escapeHtml($text) . '</div>';
        }
        if ($mode === Config::EMAIL_QUADERNO) {
            $text = (string)__(
                'Customer emails: Quaderno. Magento sends no invoice or credit memo emails for these documents;'
                . ' switch on Autosend in Quaderno (Settings > Document preferences > Billing). Simplified'
                . ' invoices sent without the customer\'s email are emailed by Magento after issue.'
            );
            return '<div class="message message-notice verifactu-email-notice">'
                . $this->escaper->escapeHtml($text) . '</div>';
        }
        $text = (string)__(
            'Magento\'s own invoice and credit memo emails show the Magento number, not the Verifactu invoice,'
            . ' and are sent before Quaderno issues it. The legal invoice is the one Quaderno issues (PDF and'
            . ' AEAT link in the customer account). Disable these emails in Stores > Configuration > Sales >'
            . ' Sales Emails (Invoice, Credit Memo), or change their templates so they do not look like an invoice.'
        ) . ' ' . (string)__('Or set Customer emails to "After issue" below.');
        return '<div class="message message-warning verifactu-email-notice">'
            . $this->escaper->escapeHtml($text) . '</div>';
    }

    /**
     * Customer email mode in the scope shown on the config page.
     *
     * @return string
     */
    private function mode(): string
    {
        $store = (string)$this->request->getParam('store');
        $website = (string)$this->request->getParam('website');
        if ($store !== '') {
            $value = $this->scopeConfig->getValue(
                Config::XML_CUSTOMER_EMAIL_MODE,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        } elseif ($website !== '') {
            $value = $this->scopeConfig->getValue(
                Config::XML_CUSTOMER_EMAIL_MODE,
                ScopeInterface::SCOPE_WEBSITE,
                $website
            );
        } else {
            $value = $this->scopeConfig->getValue(Config::XML_CUSTOMER_EMAIL_MODE);
        }
        return (string)$value;
    }
}
