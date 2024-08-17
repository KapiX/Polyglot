#!/usr/bin/env bash
XDEBUG_MODE=coverage php artisan test --coverage
echo "Results saved to: $(pwd)/tests/Coverage/html/index.html"
