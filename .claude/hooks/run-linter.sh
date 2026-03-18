#!/bin/bash
# Runs linter after code changes
# Can be used as PostToolUse hook

INPUT=$(cat)
TOOL=$(echo "$INPUT" | jq -r '.tool_name // empty')
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')

# Skip if no file path
if [ -z "$FILE_PATH" ]; then
    exit 0
fi

# Only run for PHP files
if [[ "$FILE_PATH" == *.php ]]; then
    # Check if PHP is available
    if ! command -v php &> /dev/null; then
        echo "LINTER: PHP not found on this system. Skipping syntax check." >&2
        echo "To enable PHP linting, install PHP: https://www.php.net/manual/en/install.php" >&2
        exit 0
    fi

    # Check if file exists
    if [ -f "$FILE_PATH" ]; then
        # Run PHP syntax check
        php -l "$FILE_PATH" 2>&1
        PHP_EXIT_CODE=$?

        if [ $PHP_EXIT_CODE -ne 0 ]; then
            echo "LINTER: Syntax error detected in $FILE_PATH" >&2
            exit 1
        fi

        echo "LINTER: PHP syntax OK for $FILE_PATH"
    else
        echo "LINTER: File not found: $FILE_PATH" >&2
    fi
fi

# Check for JavaScript/TypeScript files
if [[ "$FILE_PATH" == *.js ]] || [[ "$FILE_PATH" == *.ts ]] || [[ "$FILE_PATH" == *.tsx ]]; then
    # Check if node is available
    if ! command -v node &> /dev/null; then
        echo "LINTER: Node.js not found. Skipping JS/TS check." >&2
        exit 0
    fi

    # Check if eslint is available (project-local or global)
    ESLINT=""
    if [ -f "./node_modules/.bin/eslint" ]; then
        ESLINT="./node_modules/.bin/eslint"
    elif command -v eslint &> /dev/null; then
        ESLINT="eslint"
    fi

    if [ -n "$ESLINT" ] && [ -f "$FILE_PATH" ]; then
        $ESLINT "$FILE_PATH" --format compact 2>&1
        ESLINT_EXIT=$?

        if [ $ESLINT_EXIT -ne 0 ]; then
            echo "LINTER: ESLint issues in $FILE_PATH" >&2
            # Don't fail the hook for linting issues, just warn
            exit 0
        fi
    fi
fi

# Check for Python files
if [[ "$FILE_PATH" == *.py ]]; then
    # Check if python3 is available
    if ! command -v python3 &> /dev/null; then
        echo "LINTER: Python3 not found. Skipping Python check." >&2
        exit 0
    fi

    # Run Python syntax check
    if [ -f "$FILE_PATH" ]; then
        python3 -m py_compile "$FILE_PATH" 2>&1
        PYTHON_EXIT=$?

        if [ $PYTHON_EXIT -ne 0 ]; then
            echo "LINTER: Python syntax error in $FILE_PATH" >&2
            exit 1
        fi

        echo "LINTER: Python syntax OK for $FILE_PATH"
    fi
fi

exit 0