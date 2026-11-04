#!/usr/bin/env bash
# Static checks and unit tests for the module. Run from the module directory, with the vendor/bin
# of a Magento project as $1 (default /var/www/html/vendor/bin). One source for local runs
# (CONTRIBUTING.md) and CI (.github/workflows/module.yml). Exit code != 0 if any gate fails.
# Covers the Adobe Commerce Marketplace code sniffer (severity 10) and stricter checks.
set -uo pipefail
V=${1:-/var/www/html/vendor/bin}
fail=0
# Drop PHPCS 3.13 deprecation chatter (CSS/JS/GraphQL tokenizers, one deprecated CSS sniff)
quiet() { grep -vE '^DEPRECATED: |is listening for (CSS|JS|GRAPHQL)|uses 1 deprecated sniff|^-{20,}$|Squiz\.CSS\.NamedColours|has been deprecated since|Support for scanning CSS|Deprecated sniffs are still run|^future\.$|^$' ; true; }
step() { printf '\n== %s\n' "$1"; }
has_php() { find . -name "*.php" -not -path "./vendor/*" | grep -q .; }

step "Marketplace code sniffer (exact EQP command: severity-10 errors block)"
$V/phpcs --standard=Magento2 --extensions=php,phtml --error-severity=10 --warning-severity=0 \
    --ignore-annotations --ignore=vendor/* --report=summary . 2>&1 | quiet
[ "${PIPESTATUS[0]}" -eq 0 ] || fail=1

step "phpcs, our gate: Magento2 + PHPCompatibility 8.1-, warnings included"
$V/phpcs --standard=phpcs.xml.dist --report=full . 2>&1 | quiet
[ "${PIPESTATUS[0]}" -eq 0 ] || fail=1

step "PHPStan level 6"
if has_php; then
  php -d memory_limit=1G $V/phpstan analyse -c phpstan.neon.dist --no-progress --error-format=table 2>&1 \
    | awk '/old version of PHPStan/{skip=1} skip && /in your composer.json/{skip=0; next} !skip'
  [ "${PIPESTATUS[0]}" -eq 0 ] || fail=1
else echo "no PHP files"; fi

step "PHPMD"
if has_php; then $V/phpmd . text phpmd.xml.dist --exclude vendor || fail=1; else echo "no PHP files"; fi

step "Copy-paste (PHPCPD, as in the Marketplace copy-paste check)"
# $PHPCPD overrides the binary (CI uses the phar)
if has_php; then "${PHPCPD:-$V/phpcpd}" --exclude vendor . || fail=1; else echo "no PHP files"; fi

step "No TODO/FIXME (Marketplace package verification)"
if grep -rnIE 'TODO|FIXME' --exclude-dir=vendor --include='*.php' --include='*.phtml' --include='*.xml' --include='*.js' .; then
  fail=1
else echo "none"; fi

step "composer validate"
# The Marketplace requires the "version" field; Composer only warns about it. Any other
# warning or error fails the gate.
if test -f composer.json; then
  out=$(composer validate --no-check-publish 2>&1); rc=$?
  echo "$out"
  other=$(echo "$out" | grep -E '^- ' | grep -v 'The version field is present')
  { [ $rc -ne 0 ] || [ -n "$other" ]; } && fail=1
else echo "no composer.json yet"; fi

step "Unit tests"
if test -d Test/Unit; then php $V/phpunit -c phpunit.xml.dist || fail=1; else echo "no Test/Unit"; fi

echo
[ $fail -eq 0 ] && echo "ALL CHECKS PASSED" || echo "CHECKS FAILED"
exit $fail
