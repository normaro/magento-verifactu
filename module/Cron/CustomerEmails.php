<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Cron;

use Normaro\Verifactu\Model\CustomerEmail\Dispatcher;
use Normaro\Verifactu\Model\Document;
use Normaro\Verifactu\Model\DocumentRepository;
use Normaro\Verifactu\Model\ResourceModel\CustomerEmail;
use Normaro\Verifactu\Model\Sender;

/**
 * Every minute: refresh the issued documents whose customer email waits for the AEAT registration, and send
 * the email once the result is in or the wait (Dispatcher::MAX_WAIT_SECONDS) is over. Only these few rows
 * are read from the provider; the 10-minute recheck covers the rest.
 */
class CustomerEmails
{
    private const BATCH = 50;

    /**
     * @param CustomerEmail $customerEmail
     * @param DocumentRepository $documentRepository
     * @param Sender $sender
     * @param Dispatcher $dispatcher
     */
    public function __construct(
        private readonly CustomerEmail $customerEmail,
        private readonly DocumentRepository $documentRepository,
        private readonly Sender $sender,
        private readonly Dispatcher $dispatcher
    ) {
    }

    /**
     * Run the job.
     *
     * @return void
     */
    public function execute(): void
    {
        foreach ($this->customerEmail->findPending(self::BATCH) as $pending) {
            $row = $this->documentRepository->findBySource($pending['source_type'], $pending['source_id']);
            $status = $row ? (string)$row->getData('status') : '';
            if ($row === null
                || !in_array($status, [Document::STATUS_ISSUED, Document::STATUS_ISSUED_WITH_WARNING], true)
            ) {
                continue; // not issued yet: the email waits for the consumer / Resend
            }
            if ((string)$row->getData('aeat_status') === 'pending') {
                $this->sender->refresh($row); // stores the AEAT result and calls the dispatcher
            }
            $this->dispatcher->afterIssued($row);
        }
    }
}
