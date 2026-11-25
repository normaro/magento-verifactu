<?php
/**
 * Copyright (c) Iurii Tkachenko. Licensed under OSL-3.0, see LICENSE.
 */
declare(strict_types=1);

namespace Normaro\Verifactu\Model\Provider;

/**
 * Customer of a document, provider-neutral.
 */
class CustomerData
{
    public const KIND_PERSON = 'person';
    public const KIND_COMPANY = 'company';

    /**
     * Value object; all fields are constructor arguments.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) value object: the flag is data, not a code path
     *
     * @param string $kind person or company
     * @param string $firstName person first name, or the company name
     * @param string $lastName
     * @param string $country ISO 3166-1 alpha-2
     * @param string|null $taxId NIF / VAT number without spaces
     * @param string|null $street1
     * @param string|null $street2
     * @param string|null $city
     * @param string|null $postcode
     * @param string|null $region
     * @param string|null $email
     * @param string|null $contactId existing provider contact
     * @param bool $detached sent without email and stored contact (simplified invoice, see detached())
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $country,
        public readonly ?string $taxId = null,
        public readonly ?string $street1 = null,
        public readonly ?string $street2 = null,
        public readonly ?string $city = null,
        public readonly ?string $postcode = null,
        public readonly ?string $region = null,
        public readonly ?string $email = null,
        public readonly ?string $contactId = null,
        public readonly bool $detached = false
    ) {
    }

    /**
     * The same customer without email and without the stored provider contact.
     *
     * Quaderno issues a full invoice instead of a simplified one (receipt) when the contact has an
     * email (Quaderno sandbox, 2026-11-25); a detached customer is also not remembered as the
     * registered customer's contact.
     *
     * @return self
     */
    public function detached(): self
    {
        return new self(
            $this->kind,
            $this->firstName,
            $this->lastName,
            $this->country,
            $this->taxId,
            $this->street1,
            $this->street2,
            $this->city,
            $this->postcode,
            $this->region,
            null,
            null,
            true
        );
    }
}
