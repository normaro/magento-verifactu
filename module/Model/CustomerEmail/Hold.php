<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\CustomerEmail;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Normaro\Verifactu\Model\Config;
use Normaro\Verifactu\Model\Document;
use Normaro\Verifactu\Model\DocumentRepository;
use Normaro\Verifactu\Model\ResourceModel\CustomerEmail;

/**
 * Decides whether Magento's invoice / credit memo email goes out now or waits.
 */
class Hold
{
    private const ISSUED = [Document::STATUS_ISSUED, Document::STATUS_ISSUED_WITH_WARNING];

    /**
     * @param Config $config
     * @param Gate $gate
     * @param DocumentRepository $documentRepository
     * @param DetachedSale $detachedSale
     */
    public function __construct(
        private readonly Config $config,
        private readonly Gate $gate,
        private readonly DocumentRepository $documentRepository,
        private readonly DetachedSale $detachedSale
    ) {
    }

    /**
     * Status to record when the email is held, or null when Magento sends it now.
     *
     * In scope = the module is enabled for the document's store (not: a module row exists).
     *
     * @param Invoice|Creditmemo $document
     * @return string|null
     */
    public function decide(Invoice|Creditmemo $document): ?string
    {
        $storeId = (int)$document->getStoreId();
        if ($this->gate->isOpen() || !$this->config->isEnabled($storeId)) {
            return null;
        }
        $mode = $this->config->getCustomerEmailMode($storeId);
        if ($mode === Config::EMAIL_AS_IS) {
            return null;
        }
        if ($mode === Config::EMAIL_QUADERNO && !$this->detachedSale->isDetached($document)) {
            return CustomerEmail::STATUS_QUADERNO;
        }
        return $this->isIssued($document) ? null : CustomerEmail::STATUS_PENDING;
    }

    /**
     * Source type of the document.
     *
     * @param Invoice|Creditmemo $document
     * @return string
     */
    public function sourceType(Invoice|Creditmemo $document): string
    {
        return $document instanceof Creditmemo ? Document::SOURCE_CREDITMEMO : Document::SOURCE_INVOICE;
    }

    /**
     * Whether the Verifactu document is already issued.
     *
     * @param Invoice|Creditmemo $document
     * @return bool
     */
    private function isIssued(Invoice|Creditmemo $document): bool
    {
        $row = $this->documentRepository->findBySource($this->sourceType($document), (int)$document->getId());
        return $row !== null && in_array((string)$row->getData('status'), self::ISSUED, true);
    }
}
