<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Block\Email;

use Magento\Framework\View\Element\Template;
use Normaro\Verifactu\Model\CustomerEmail\TemplateData;
use Normaro\Verifactu\Model\Document as VerifactuDocument;

/**
 * Verifactu line in Magento's invoice / credit memo email, put before the items by Plugin\EmailItemsVerifactuLine.
 */
class Document extends Template
{
    /** @var string */
    protected $_template = 'Normaro_Verifactu::email/document.phtml';

    /**
     * @param Template\Context $context
     * @param TemplateData $templateData
     * @param array<string,mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly TemplateData $templateData,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Issued Verifactu row of the emailed document (data "source_type", "source_id"), or null.
     *
     * @return VerifactuDocument|null
     */
    public function getRow(): ?VerifactuDocument
    {
        return $this->templateData->issuedRow(
            (string)$this->getData('source_type'),
            (int)$this->getData('source_id')
        );
    }

    /**
     * Label of the Verifactu document type.
     *
     * @param VerifactuDocument $row
     * @return string
     */
    public function getTypeLabel(VerifactuDocument $row): string
    {
        $type = (string)$row->getData('type');
        $label = VerifactuDocument::TYPE_LABELS[$type] ?? $type;
        return (string)__($label);
    }
}
