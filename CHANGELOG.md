# Changelog

All notable changes to this project. Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
versions follow [Semantic Versioning](https://semver.org/).

## [0.1.2] - 2026-11-25

Magento 2.4.9 / PHP 8.5 support. No change in behaviour.

### Changed

- `composer.json`: every `magento/*` constraint open up to the next major version (for example
  `magento/framework >=103.0.7 <104`), so the package installs on Magento 2.4.9; `php`
  `>=8.1 <8.6`. Version 0.1.2.
- Unit tests: data providers also declared with `#[DataProvider]` (PHPUnit 12 on 2.4.9 reads
  attributes only); PHPStan config works with PHPStan 1 and 2.
- CI: Magento 2.4.9 with PHP 8.4 and 8.5 added.
- README: compatibility Magento Open Source 2.4.7–2.4.9.

## [0.1.1] - 2026-11-19

Documentation only; no code changes.

### Changed

- README: compatibility stated as Magento Open Source 2.4.7 and 2.4.8 (Adobe Commerce not
  tested); "pre-release" label and the paragraph about paid services removed.
- `composer.json` version 0.1.1.

## [0.1.0] - 2026-11-11

First release. Tested against the Quaderno sandbox on Magento 2.4.7 (PHP 8.3), including
production mode with Varnish; static checks and unit tests in CI on 2.4.7 (PHP 8.1–8.3) and
2.4.8 (PHP 8.4).

### Added

- Magento invoice → Quaderno invoice or receipt (simplified invoice); credit memo → credit note
  linked to the original.
- Database message queue and consumer `verifactu.document.send`; no provider call during
  checkout or an admin save. Retries on network errors, 5xx and 429 without duplicates; cron
  job `verifactu_recheck` refreshes the AEAT state.
- Checks before sending, with the reason in the admin and an admin notification: missing NIF at
  or above the simplified-invoice threshold, or for Canarias / Ceuta / Melilla; NIF check letter;
  incomplete billing address; totals that do not add up; unmapped tax class; credit memo rules.
- Verifactu number, AEAT state, verification link, QR URL and PDF on the invoice and credit memo
  pages, in the sales grids, in **Sales › Operations › Verifactu Documents** (filters, mass
  resend) and in the customer's order view. Resend button.
- Admin notification when a document is blocked, rejected, left as a draft or fails for good;
  Read Details opens the invoice or credit memo.
- Configuration: sandbox / live, encrypted API key, invoice trigger, threshold, tax class → IVA
  mapping, debug log (`var/log/verifactu.log`, tax IDs always masked). A notice at the top of the
  section about Magento's own invoice and credit memo emails.
- Translations: `en_US`, `es_ES`.
- Installation checklist (`module/docs/install-checklist.md`).

### Known limitations

- Magento's own invoice and credit memo emails are not changed: they carry the Magento number
  and go out before Quaderno issues the invoice (see the installation checklist).
- One credit note per invoice through the API; a second refund is blocked and has to be created
  in Quaderno. Adjustment refunds, fees and gift cards are not sent.
- Block messages are stored in the language of the queue consumer, not translated when shown.
