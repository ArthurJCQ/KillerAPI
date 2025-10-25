REPORTS_DIR ?= build/reports

.PHONY: audit
audit: phpcs phpstan

.PHONY: prepare-ci
prepare-ci:
	@mkdir -p build/reports

.PHONY: phpstan
phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=-1 src tests --level 9

.PHONY: phpstan-ci
phpstan-ci: prepare-ci
	vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=-1 --level max --error-format checkstyle src tests | awk NF > $(REPORTS_DIR)/phpstan.xml

.PHONY: phpcs
phpcs:
	vendor/bin/phpcs -sp --standard=phpcs.xml.dist --extensions=php --ignore=*/tests/bootstrap.php src tests

.PHONY: phpcs-ci
phpcs-ci: prepare-ci
	vendor/bin/phpcs -sp --report=checkstyle --report-file=$(REPORTS_DIR)/phpcs.xml --standard=phpcs.xml.dist --extensions=php --ignore=*/tests/bootstrap.php src tests

phpmd:
	 vendor/bin/phpmd src text phpmd.xml

.PHONY: unit-tests
unit-tests:
	@vendor/bin/codecept run Unit

.PHONY: unit-tests-coverage
unit-tests-coverage:
	XDEBUG_MODE=coverage php -dauto_prepend_file=bin/xdebug_coverage_filter.php vendor/bin/codecept run Unit --coverage

.PHONY: unit-tests
unit-tests-ci: prepare-ci
	@vendor/bin/codecept run Unit --xml $(PWD)/$(REPORTS_DIR)/unit-tests.xml

.PHONY: functional-tests
functional-tests:
	@bin/functional_tests.sh $(PHPUNIT_EXTRA_ARGS)

.PHONY: functional-tests-ci
functional-tests-ci: prepare-ci ## Run functional tests and generate report file
	- bin/functional_tests.sh --xml $(PWD)/$(REPORTS_DIR)/functional-tests.xml $(PHPUNIT_EXTRA_ARGS)
