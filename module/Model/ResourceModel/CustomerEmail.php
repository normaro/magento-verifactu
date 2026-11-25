<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * State of the customer email of a Magento invoice / credit memo (After issue / Quaderno modes).
 */
class CustomerEmail
{
    public const TABLE = 'verifactu_customer_email';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_SENT_MANUALLY = 'sent_manually';
    public const STATUS_FAILED = 'failed';
    public const STATUS_QUADERNO = 'quaderno';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Waiting for the Verifactu invoice',
        self::STATUS_SENT => 'Sent',
        self::STATUS_SENT_MANUALLY => 'Sent from the admin',
        self::STATUS_FAILED => 'Not sent (error)',
        self::STATUS_QUADERNO => 'Sent by Quaderno',
    ];

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    /**
     * Status of the email, or null when Magento did not ask for one.
     *
     * @param string $sourceType
     * @param int $sourceId
     * @return string|null
     */
    public function getStatus(string $sourceType, int $sourceId): ?string
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), ['status'])
            ->where('source_type = ?', $sourceType)
            ->where('source_id = ?', $sourceId);
        $value = (string)$connection->fetchOne($select);
        return $value === '' ? null : $value;
    }

    /**
     * Store or replace the status.
     *
     * @param string $sourceType
     * @param int $sourceId
     * @param int $storeId
     * @param string $status
     * @param string|null $message
     * @return void
     */
    public function save(string $sourceType, int $sourceId, int $storeId, string $status, ?string $message = null): void
    {
        $this->resource->getConnection()->insertOnDuplicate(
            $this->resource->getTableName(self::TABLE),
            [
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'store_id' => $storeId,
                'status' => $status,
                'message' => $message,
            ],
            ['status', 'message']
        );
    }

    /**
     * Change the status of an existing record only.
     *
     * @param string $sourceType
     * @param int $sourceId
     * @param string $status
     * @param string|null $message
     * @return void
     */
    public function update(string $sourceType, int $sourceId, string $status, ?string $message = null): void
    {
        $this->resource->getConnection()->update(
            $this->resource->getTableName(self::TABLE),
            ['status' => $status, 'message' => $message],
            ['source_type = ?' => $sourceType, 'source_id = ?' => $sourceId]
        );
    }
}
