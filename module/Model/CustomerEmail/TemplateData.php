<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\CustomerEmail;

use Normaro\Verifactu\Model\Document;
use Normaro\Verifactu\Model\DocumentRepository;

/**
 * Verifactu data of an issued Magento invoice / credit memo for its email.
 */
class TemplateData
{
    /**
     * @param DocumentRepository $documentRepository
     */
    public function __construct(private readonly DocumentRepository $documentRepository)
    {
    }

    /**
     * The issued row of the Magento document, or null.
     *
     * @param string $sourceType
     * @param int $sourceId
     * @return Document|null
     */
    public function issuedRow(string $sourceType, int $sourceId): ?Document
    {
        if ($sourceId <= 0) {
            return null;
        }
        $row = $this->documentRepository->findBySource($sourceType, $sourceId);
        $status = $row ? (string)$row->getData('status') : '';
        return in_array($status, [Document::STATUS_ISSUED, Document::STATUS_ISSUED_WITH_WARNING], true)
            ? $row : null;
    }

    /**
     * Template variables (empty strings when the document is not issued).
     *
     * @param string $sourceType
     * @param int $sourceId
     * @return array<string,string>
     */
    public function variables(string $sourceType, int $sourceId): array
    {
        $row = $this->issuedRow($sourceType, $sourceId);
        $type = $row ? (string)$row->getData('type') : '';
        $typeLabel = Document::TYPE_LABELS[$type] ?? $type;
        return [
            'verifactu_number' => $row ? (string)$row->getData('number') : '',
            'verifactu_type' => $type === '' ? '' : (string)__($typeLabel),
            'verifactu_pdf_url' => $row ? (string)$row->getData('pdf_url') : '',
            'verifactu_verification_url' => $row ? (string)$row->getData('verification_permalink') : '',
        ];
    }
}
