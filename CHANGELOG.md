# Changelog

All notable changes to `nalabdou/algebra-csv` are documented here.

## [1.0.0] 

### Added
- `Contract\CsvAdapterInterface` — common contract for all CSV adapters; extends `AdapterInterface` and adds `::from()` and `getOptions()`
- `Contract\CsvParserInterface` — contract for the low-level parser; makes the parser swappable via DI
- `Contract\DelimiterDetectorInterface` — contract for delimiter detection strategies
- `CsvFileAdapter::from(CsvOptions)` — named static constructor replacing factory usage
- `CsvStringAdapter::from(CsvOptions)` — named static constructor
- `CsvResourceAdapter::from(CsvOptions)` — named static constructor
- `CsvString::from(string, CsvOptions)` — named static constructor for the value object
- `CsvFileAdapter::getOptions()` / `CsvStringAdapter::getOptions()` / `CsvResourceAdapter::getOptions()`
- `Test coverage enforced in `phpunit.xml.dist` with Clover + HTML reports
- `tests/Unit/Contract/CsvAdapterInterfaceTest.php` — cross-adapter contract assertions
- `tests/Unit/Exception/ExceptionTest.php` — coverage for all exception classes
- `.github/workflows/ci.yml` — matrix CI for PHP 8.2 / 8.3 / 8.4
- `.github/ISSUE_TEMPLATE/bug_report.yml` + `feature_request.yml`
- `.github/PULL_REQUEST_TEMPLATE.md`
- `SECURITY.md`