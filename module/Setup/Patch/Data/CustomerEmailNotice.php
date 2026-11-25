<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Setup\Patch\Data;

use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * 0.2.0: one admin notification about the new "Customer emails" setting.
 *
 * 0.1 told merchants to switch Magento's invoice / credit memo emails off; "After issue" needs them on.
 */
class CustomerEmailNotice implements DataPatchInterface
{
    /**
     * @param NotifierInterface $notifier
     */
    public function __construct(private readonly NotifierInterface $notifier)
    {
    }

    /**
     * Add the notification.
     *
     * @return $this
     */
    public function apply()
    {
        $this->notifier->addNotice(
            (string)__('Verifactu: Magento emails can now wait for the Verifactu invoice'),
            (string)__(
                'New setting Stores > Configuration > Sales > Verifactu > Customer emails. "After issue" holds'
                . ' Magento\'s invoice and credit memo emails until Quaderno has issued the Verifactu invoice, then'
                . ' sends them with its number, PDF and AEAT link. If you switched Sales Emails > Invoice / Credit'
                . ' Memo off for Verifactu, switch them back on when you choose "After issue".'
            )
        );
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
