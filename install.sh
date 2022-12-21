#!/usr/bin/env bash

# Install backend, run database migrations and generate OpenAPI files.
cd backend || exit
if [[ $1 = prod ]]; then
    composer install --no-dev --optimize-autoloader --no-interaction
    composer compile:prod --no-dev --no-interaction
else
    composer install
    composer compile
fi

# Generate and build OpenAPI JavaScript client
cd ../frontend && ./openapi.sh
cd neucore-js-client || exit
npm install
npm run build

# Build frontend
cd .. && npm install
if [[ $1 = prod ]]; then
    npm run build
fi
