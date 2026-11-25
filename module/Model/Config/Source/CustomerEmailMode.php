<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Normaro\Verifactu\Model\Config;

/**
 * Who emails the customer the invoice / credit memo.
 */
class CustomerEmailMode implements OptionSourceInterface
{
    /**
     * Options with translated labels.
     *
     * @return array<int,array{value:string,label:\Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::EMAIL_AS_IS, 'label' => __('As is: Magento sends when the document is saved')],
            [
                'value' => Config::EMAIL_AFTER_ISSUE,
                'label' => __('After issue: Magento sends with the Verifactu invoice'),
            ],
            ['value' => Config::EMAIL_QUADERNO, 'label' => __('Quaderno: Quaderno sends (Autosend)')],
        ];
    }
}
