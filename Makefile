.PHONY: phpstan
phpstan: ## Run PHPStan
	vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=-1 src tests --level max

.PHONY: phpcs
phpcs: ## Run PHP_CodeSniffer
	vendor/bin/phpcs --standard=phpcs.xml --extensions=php --ignore=*/tests/bootstrap.php src tests

.PHONY: unit-tests
unit-tests: ## Run unit tests
	@vendor/bin/phpunit --exclude-group functional

.PHONY: functional-tests
functional-tests: ## Run functional tests
	@bin/functional_tests.sh $(PHPUNIT_EXTRA_ARGS)
