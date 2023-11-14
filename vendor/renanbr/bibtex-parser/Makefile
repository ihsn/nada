ifeq ($(shell type podman > /dev/null 2>&1; echo $$?), 0)
	ENGINE ?= podman
else ifeq ($(shell type docker > /dev/null 2>&1; echo $$?), 0)
	ENGINE ?= docker
endif

PHP_VERSION ?= 7.4

IMAGE_BASE = jakzal/phpqa:php$(PHP_VERSION)
IMAGE = renanbr/bibtex-parser:php$(PHP_VERSION)
LABEL = maintainer=renanbr-bibtex-parser

RUN = $(ENGINE) run --init -it --rm -v "$(CURDIR):/project" -w /project

.DEFAULT_GOAL := help

help: ## Display this message help
	@make -v | head -n 1
	@awk '\
		BEGIN {\
			FS = ":.*##";\
			printf "\n\033[33mUsage:\033[0m\n  [PHP_VERSION=major.minor] make [target]\n\n\033[33mAvailable targets:\033[0m\n" \
		} /^[a-zA-Z0-9_-]+:.*?##/ { \
			printf "  \033[32m%-18s\033[0m %s\n", $$1, $$2 \
		} /^##/ { \
			printf "\033[33m %s\033[0m\n", substr($$0, 4) \
		}' $(MAKEFILE_LIST)
.PHONY: help

## Checks

check: static-analysis cs-check test ## Run all checks

static-analysis: vendor ## Run static analysis
	$(RUN) $(IMAGE) phpstan analyse --verbose
.PHONY: static-analysis

cs-check: check-engine ## Check for coding standards violations
	mkdir -p var
	$(RUN) $(IMAGE_BASE) php-cs-fixer fix --dry-run --verbose
.PHONY: cs-check

test: vendor ## Run tests
	$(RUN) $(IMAGE) php -d pcov.enabled=1 ./vendor/bin/phpunit --testdox --coverage-text --verbose
.PHONY: test

## Fixers

cs-fix: check-engine ## Fix coding standards
	mkdir -p var
	$(RUN) $(IMAGE_BASE) php-cs-fixer fix
.PHONY: cs-fix

## Misc

clean: check-engine ## Clean up workspace
	$(RUN) $(IMAGE_BASE) rm -rf composer.lock var/ vendor/
	$(ENGINE) image rm --force $$($(ENGINE) images --filter "label=$(LABEL)" --quiet) 2>&1 | true
.PHONY: clean

vendor: build-image
	$(RUN) $(IMAGE) composer install -vvv

## Container engine

check-engine:
ifeq ($(ENGINE),)
	$(error "Container engine not found. Did you install podman or docker?")
endif
.PHONY: check-engine

build-image: check-engine
	$(ENGINE) build --tag $(IMAGE) --build-arg FROM=$(IMAGE_BASE) --label $(LABEL) .docker/
.PHONY: build-image
