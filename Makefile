REPORTS_DIR ?= build/reports

.PHONY: audit
audit: phpcs phpstan

.PHONY: prepare-ci
prepare-ci:
	@mkdir -p build/reports

.PHONY: phpstan
phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=-1 src tests --level max

.PHONY: phpstan-ci
phpstan-ci: prepare-ci
	vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=-1 --level max --error-format checkstyle src tests | awk NF > $(REPORTS_DIR)/phpstan.xml

.PHONY: phpcs
phpcs:
	vendor/bin/phpcs --standard=phpcs.xml --extensions=php --ignore=*/tests/bootstrap.php src tests

.PHONY: phpcs-ci
phpcs-ci: prepare-ci
	vendor/bin/phpcs --report=checkstyle --report-file=$(REPORTS_DIR)/phpcs.xml --standard=phpcs.xml.dist --extensions=php src tests

.PHONY: unit-tests
unit-tests:
	@vendor/bin/codecept run Unit

.PHONY: unit-tests
unit-tests-ci: prepare-ci
	@vendor/bin/codecept run Unit --xml $(PWD)/$(REPORTS_DIR)/unit-tests.xml

.PHONY: functional-tests
functional-tests:
	@bin/functional_tests.sh $(PHPUNIT_EXTRA_ARGS)

.PHONY: functional-tests-ci
functional-tests-ci: prepare-ci ## Run functional tests and generate report file
	- bin/functional_tests.sh --xml $(PWD)/$(REPORTS_DIR)/functional-tests.xml $(PHPUNIT_EXTRA_ARGS)
