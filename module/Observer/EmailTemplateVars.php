<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Normaro\Verifactu\Model\CustomerEmail\TemplateData;
use Normaro\Verifactu\Model\Document;

/**
 * email_invoice_set_template_vars_before / email_creditmemo_set_template_vars_before:
 * verifactu_number, verifactu_type, verifactu_pdf_url, verifactu_verification_url.
 */
class EmailTemplateVars implements ObserverInterface
{
    /**
     * @param TemplateData $templateData
     */
    public function __construct(private readonly TemplateData $templateData)
    {
    }

    /**
     * Add the variables to the email transport.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $transport = $observer->getEvent()->getData('transportObject');
        if (!$transport instanceof DataObject) {
            return;
        }
        $creditmemo = $transport->getData('creditmemo');
        $invoice = $transport->getData('invoice');
        if ($creditmemo instanceof Creditmemo) {
            $vars = $this->templateData->variables(Document::SOURCE_CREDITMEMO, (int)$creditmemo->getId());
        } elseif ($invoice instanceof Invoice) {
            $vars = $this->templateData->variables(Document::SOURCE_INVOICE, (int)$invoice->getId());
        } else {
            return;
        }
        $transport->addData($vars);
    }
}
