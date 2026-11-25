<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Plugin;

use Magento\Sales\Block\Items\AbstractItems;
use Magento\Sales\Block\Order\Email\Creditmemo\Items as CreditmemoItems;
use Magento\Sales\Block\Order\Email\Invoice\Items as InvoiceItems;
use Normaro\Verifactu\Block\Email\Document;
use Normaro\Verifactu\Model\Document as VerifactuDocument;

/**
 * Puts the Verifactu line (number, PDF, AEAT link) above the items of Magento's invoice / credit memo email.
 *
 * The email's {{layout}} directive outputs only the first top-level block of the handle ("items"), so a
 * block of our own in the handle would not be printed: the line is prepended to the items block instead.
 */
class EmailItemsVerifactuLine
{
    /**
     * Prepend the line when the document is issued.
     *
     * @param AbstractItems $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(AbstractItems $subject, $result)
    {
        if ($subject instanceof InvoiceItems) {
            $document = $subject->getInvoice();
            $type = VerifactuDocument::SOURCE_INVOICE;
        } elseif ($subject instanceof CreditmemoItems) {
            $document = $subject->getCreditmemo();
            $type = VerifactuDocument::SOURCE_CREDITMEMO;
        } else {
            return $result;
        }
        $sourceId = $document ? (int)$document->getEntityId() : 0;
        if ($sourceId <= 0) {
            return $result;
        }
        $line = $subject->getLayout()->createBlock(Document::class, '', [
            'data' => ['source_type' => $type, 'source_id' => $sourceId],
        ]);
        return $line->toHtml() . $result;
    }
}
