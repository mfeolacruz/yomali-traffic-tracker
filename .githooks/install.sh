#!/bin/bash

echo "Installing git hooks..."
git config core.hooksPath .githooks
echo "✅ Git hooks installed successfully!"
echo ""
echo "Hooks installed:"
echo "  - pre-commit: Runs code quality checks"
echo "  - commit-msg: Validates commit message format"