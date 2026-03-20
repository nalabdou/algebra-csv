.PHONY: test unit integration coverage stan cs cs-fix ci clean run-demo help

PHP     = php
PHPUNIT = vendor/bin/phpunit
STAN    = vendor/bin/phpstan
CS      = vendor/bin/php-cs-fixer

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

test: ## Run all tests
	XDEBUG_MODE=coverage $(PHPUNIT)

unit: ## Run unit tests only
	$(PHPUNIT) --testsuite unit

integration: ## Run integration tests only
	$(PHPUNIT) --testsuite integration

coverage: ## Generate HTML coverage report → coverage/
	XDEBUG_MODE=coverage $(PHPUNIT) --coverage-html coverage

stan: ## Run PHPStan static analysis
	$(STAN) analyse

cs: ## Check code style (dry-run)
	$(CS) check --diff

cs-fix: ## Auto-fix code style
	$(CS) fix

ci: cs stan test run-demo

# ── Cleanup ───────────────────────────────────────────────────────────────────
clean:
	rm -rf vendor coverage .php-cs-fixer.cache .phpunit.result.cache .phpunit.cache

run-demo:
	@for file in demo/*.php; do \
		echo "Running $$file"; \
		php "$$file"; \
		echo ""; \
	done