# Verifactu for Magento 2 (via Quaderno)

[![module](https://github.com/normaro/magento-verifactu/actions/workflows/module.yml/badge.svg)](https://github.com/normaro/magento-verifactu/actions/workflows/module.yml)

A free, open-source Magento 2 module that makes a Spanish shop's invoices Verifactu-compliant.
Every Magento invoice and credit memo is sent to [Quaderno](https://quaderno.io), a certified
Verifactu provider, which issues the legal invoice, receipt or credit note and registers it
with the Spanish Tax Agency (AEAT). The Verifactu number, the AEAT state, the verification link
and the QR code come back into Magento.

The shop does not become an invoicing-software (SIF) producer: Quaderno is the certified
system and is responsible for the invoice records and their delivery to the AEAT.

**Status: v0.1, pre-release.** Tested against the Quaderno sandbox; not yet used in production.

## Who it is for

- **Shop owners** selling from Spain on Magento who must comply with Verifactu (from
  1 January 2027 for corporate-tax payers, from 1 July 2027 for everyone else, RDL 15/2025).
- **Developers and agencies** installing it for such shops.

## Requirements

- Magento Open Source or Adobe Commerce **2.4.7 or 2.4.8**, with the PHP version that release
  supports (CI runs PHP 8.1–8.3 on 2.4.7 and 8.4 on 2.4.8).
- A **paid Quaderno account** with the Verifactu integration activated. Activation cannot be
  undone: try everything in a Quaderno sandbox account first.
- The company's **electronic certificate** (PKCS#12) uploaded to Quaderno (Quaderno requires it
  for Verifactu), and the NIF and business name in Quaderno matching the AEAT census.
- **Magento cron** running, and the queue consumer `verifactu.document.send` running (through
  cron, which is the Magento default, or as a supervised process). Without the consumer nothing
  is sent.

## Quick start

```bash
composer require normaro/module-verifactu
bin/magento module:enable Normaro_Verifactu
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

1. **Stores › Configuration › Sales › Verifactu**: environment Sandbox, the API URL and private
   API key of your Quaderno sandbox account, the simplified-invoice threshold (the same value as
   in Quaderno), and the mapping of every product tax class and the shipping tax class to an
   IVA rate.
2. Make sure the consumer runs: with the default cron consumers runner nothing else is needed;
   if `app/etc/env.php` has a `cron_consumers_runner.consumers` allow-list, add
   `verifactu.document.send`; or run `bin/magento queue:consumers:start verifactu.document.send`
   under a supervisor.
3. Place a test order, invoice it, and open **Sales › Operations › Verifactu Documents**: the
   document should reach `issued` with an invoice number and a verification link.
4. Go live only after the sandbox works: switch the environment and the API credentials to
   your live Quaderno account.

**Customer emails.** Magento sends its own invoice and credit memo emails with the Magento number,
before Quaderno issues the Verifactu invoice. Disable them (Stores › Configuration › Sales › Sales
Emails › Invoice / Credit Memo) or change their templates so they do not look like an invoice.
The legal invoice is Quaderno's: PDF and AEAT link in the customer's order view, and by email
when "Autosend all automatic invoices and credit notes" is on in Quaderno (Document preferences).

The full list of shop settings (checkout NIF field, VIES groups for EU B2B, customer emails,
Quaderno settings) is in [module/docs/install-checklist.md](module/docs/install-checklist.md). Module
reference (every field, what is sent, retries): [module/README.md](module/README.md).

## How it works

- An invoice or credit memo is saved in Magento → the module stores a row and puts it in a
  database queue. **Nothing is sent during checkout or an admin save**, so a slow or failing
  provider never breaks an order.
- The consumer runs the checks below, then sends the document to Quaderno. Quaderno decides
  invoice vs. simplified invoice (receipt), assigns series, number and date, and registers it
  with the AEAT.
- The result is shown on the invoice and credit memo pages, in the sales grids, in the
  documents grid, and in the customer's order view.
- Network errors and provider outages are retried automatically without creating duplicates;
  a cron job also refreshes the AEAT state.

## What gets blocked, and why

Some documents would be wrong or unlawful if sent as they are. The module does not send them:
the document is marked `blocked`, the admin gets a notification with the reason, and after
fixing the data a **Resend** button sends it.

- A Spanish order **without NIF at or above the simplified-invoice threshold**: a simplified
  invoice is not allowed there, a full invoice needs the customer's NIF.
- An order to **Canarias, Ceuta or Melilla without NIF**: these sales need a full invoice at
  any amount.
- A NIF with a wrong check letter, or a billing address without street, city, postcode or
  country (Quaderno would otherwise keep the invoice as a draft).
- Totals that do not add up, or a tax class without an IVA mapping.
- A credit memo whose invoice was not issued, a second credit memo for the same invoice, or a
  credit memo above the invoice total.

## Limitations of v0.1

- One credit memo per invoice.
- Adjustment refunds, fees and gift cards are not supported (blocked).
- Not supported: sellers established in Canarias (IGIC), Ceuta or Melilla; recargo de
  equivalencia; País Vasco and Navarra (TicketBAI); SII taxpayers.
- Only Quaderno as the provider.
- No webhooks: the AEAT state is polled by cron.

## Law texts

The rules the module follows come from these texts (consolidated versions on the BOE):

- [Real Decreto 1619/2012, Reglamento de facturación](https://www.boe.es/buscar/act.php?id=BOE-A-2012-14696): invoices, simplified invoices, rectificativas.
- [Real Decreto 1007/2023, RRSIF](https://www.boe.es/buscar/act.php?id=BOE-A-2023-24840): requirements for invoicing software (Verifactu).
- [Orden HAC/1177/2024](https://www.boe.es/buscar/act.php?id=BOE-A-2024-22138): technical specifications, QR code.
- [Real Decreto-ley 15/2025](https://www.boe.es/diario_boe/txt.php?id=BOE-A-2025-24446): current Verifactu deadlines.

Not legal advice.

## Support

- Bugs and questions: [GitHub issues](https://github.com/normaro/magento-verifactu/issues).
  Never post customer data, NIFs or API keys.
- Contributions: see [CONTRIBUTING.md](CONTRIBUTING.md). Changes: [CHANGELOG.md](CHANGELOG.md).
- Installation, IVA mapping, a check of your first invoices, and a support subscription for
  Magento releases and AEAT changes: **[normaro.es](https://normaro.es)**.

## License

[OSL-3.0](LICENSE). This module is not affiliated with or endorsed by Quaderno or the AEAT.
