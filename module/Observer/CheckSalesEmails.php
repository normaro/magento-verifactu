<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Normaro\Verifactu\Model\AdminNotifier;
use Normaro\Verifactu\Model\Config;

/**
 * admin_system_config_changed_section_verifactu: Magento's emails held for the Verifactu invoice need
 * Sales Emails > Invoice / Credit Memo switched on in the same scope.
 */
class CheckSalesEmails implements ObserverInterface
{
    private const SALES_EMAILS = [
        'sales_email/invoice/enabled' => 'Invoice',
        'sales_email/creditmemo/enabled' => 'Credit Memo',
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $messageManager
     * @param AdminNotifier $notifier
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ManagerInterface $messageManager,
        private readonly AdminNotifier $notifier
    ) {
    }

    /**
     * Warn when the mode needs emails that are switched off.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        [$scope, $code] = $this->scope($observer);
        $mode = (string)$this->scopeConfig->getValue(Config::XML_CUSTOMER_EMAIL_MODE, $scope, $code);
        if ($mode !== Config::EMAIL_AFTER_ISSUE && $mode !== Config::EMAIL_QUADERNO) {
            return;
        }
        $off = [];
        foreach (self::SALES_EMAILS as $path => $label) {
            if (!$this->scopeConfig->isSetFlag($path, $scope, $code)) {
                $off[] = $label;
            }
        }
        if ($off === []) {
            return;
        }
        $text = (string)__(
            'Customer emails "%1" needs Stores > Configuration > Sales > Sales Emails > %2 > Enabled = Yes:'
            . ' otherwise Magento sends no email after the Verifactu invoice is issued.',
            $mode === Config::EMAIL_AFTER_ISSUE ? __('After issue') : __('Quaderno'),
            implode(' / ', $off)
        );
        $this->messageManager->addWarningMessage($text);
        $this->notifier->notifySalesEmailsOff($text);
    }

    /**
     * Scope of the saved section.
     *
     * @param Observer $observer
     * @return array{0:string,1:string|null}
     */
    private function scope(Observer $observer): array
    {
        $store = (string)$observer->getEvent()->getData('store');
        if ($store !== '') {
            return [ScopeInterface::SCOPE_STORE, $store];
        }
        $website = (string)$observer->getEvent()->getData('website');
        if ($website !== '') {
            return [ScopeInterface::SCOPE_WEBSITE, $website];
        }
        return [ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null];
    }
}
