<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Plugin;

use Normaro\Verifactu\Model\CustomerEmail\Gate;

/**
 * The admin's "Send Email" on an invoice / credit memo page always sends, issued or not.
 */
class ManualSendEmail
{
    /**
     * @param Gate $gate
     */
    public function __construct(private readonly Gate $gate)
    {
    }

    /**
     * Open the gate for this request.
     *
     * @param object $subject
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(object $subject)
    {
        $this->gate->openForManualSend();
        return null;
    }
}
