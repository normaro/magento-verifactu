<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\CustomerEmail;

/**
 * Lets an email through the hold: the module's own send after issue, and the admin's "Send Email".
 */
class Gate
{
    /** @var bool */
    private bool $open = false;

    /** @var bool */
    private bool $manual = false;

    /**
     * Run $send with the gate open; returns what $send returns.
     *
     * @param callable $send
     * @return mixed
     */
    public function through(callable $send): mixed
    {
        $wasOpen = $this->open;
        $this->open = true;
        try {
            return $send();
        } finally {
            $this->open = $wasOpen;
        }
    }

    /**
     * Open the gate for the rest of this request: the admin clicked "Send Email".
     *
     * @return void
     */
    public function openForManualSend(): void
    {
        $this->open = true;
        $this->manual = true;
    }

    /**
     * Whether emails pass now.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->open;
    }

    /**
     * Whether the admin asked for the email by hand in this request.
     *
     * @return bool
     */
    public function isManual(): bool
    {
        return $this->manual;
    }
}
