# Contributing

Bug reports, questions and pull requests are welcome.

## Issues

- Say which Magento and PHP version, module version, and Quaderno environment (sandbox / live).
- Include the status and reason shown in **Sales › Operations › Verifactu Documents**, and
  the relevant lines of `var/log/verifactu.log` if debug logging was on.
- **Never post customer data, NIFs / VAT numbers, your Quaderno account URL or API keys.** The
  log masks tax IDs and never writes the API key, but check before pasting.
- Tax questions ("which invoice does this sale need?") are best answered by your asesor; the
  law texts linked in the [README](README.md#law-texts) may help.

## Pull requests

- One topic per pull request, with a test where the change has logic.
- The code must pass the same gates as CI (below) on Magento 2.4.7 and 2.4.8.
- No customer data, account hosts or keys in code, fixtures or commit messages. Fixtures from a
  Quaderno sandbox must have names, tax IDs, addresses and the account host replaced.
- Support Magento 2.4.7 / 2.4.8 and PHP 8.1–8.4: no syntax newer than PHP 8.1.
- By submitting a pull request you agree to license your contribution under
  [OSL-3.0](LICENSE).

## Running the gates locally

`tools/ci/module-gates.sh` runs the Marketplace code sniffer (severity 10), PHPCS (Magento2 +
PHPCompatibility, warnings included), PHPStan level 6, PHPMD, PHPCPD, a TODO/FIXME check,
`composer validate` and the unit tests. You need a Magento 2.4.7 or 2.4.8 project with its dev
dependencies and the module installed in it:

```bash
# in the Magento project
composer config repositories.verifactu '{"type": "path", "url": "/path/to/magento-verifactu/module", "options": {"symlink": false}}'
composer require normaro/module-verifactu:@dev
composer require --dev phpmd/phpmd:2.15.0 'phpstan/phpstan:^1.12' 'bitexpert/phpstan-magento:^0.32'
curl -sSfL -o /tmp/phpcpd https://phar.phpunit.de/phpcpd-6.0.3.phar && chmod +x /tmp/phpcpd

# in the module directory of this repository
cd /path/to/magento-verifactu/module
PHPCPD=/tmp/phpcpd bash ../tools/ci/module-gates.sh /path/to/magento/vendor/bin
```

It ends with `ALL CHECKS PASSED` or `CHECKS FAILED`. The exact versions CI uses are in
[.github/workflows/module.yml](.github/workflows/module.yml).

For a manual test, use a Quaderno **sandbox** account (Verifactu cannot be switched off in a
live account), run the consumer with
`bin/magento queue:consumers:start verifactu.document.send`, and check the result in
**Sales › Operations › Verifactu Documents**.
