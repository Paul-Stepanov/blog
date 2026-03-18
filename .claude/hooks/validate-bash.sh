#!/bin/bash
# Validates Bash commands before execution
# Blocks dangerous operations

INPUT=$(cat)
COMMAND=$(echo "$INPUT" | jq -r '.tool_input.command // empty')

if [ -z "$COMMAND" ]; then
    exit 0
fi

# Block dangerous commands
DANGEROUS_PATTERNS=(
    'rm -rf /'
    'rm -rf \.'
    'rm -rf \*'
    'git push --force'
    'git push -f'
    'git reset --hard'
    'DROP TABLE'
    'DROP DATABASE'
    'TRUNCATE TABLE'
    'DELETE FROM'
    ':(){:|:&};:'
    'mkfs'
    'dd if='
    'chmod -R 777 /'
    'chown -R'
    '> /dev/sd'
)

for pattern in "${DANGEROUS_PATTERNS[@]}"; do
    if echo "$COMMAND" | grep -qiE "$pattern"; then
        echo "BLOCKED: Dangerous command pattern detected: $pattern" >&2
        exit 2
    fi
done

# Warn about potentially risky commands but allow
WARN_PATTERNS=(
    'git checkout --'
    'git clean -fd'
    'npm publish'
    'docker system prune'
    'composer global'
)

for pattern in "${WARN_PATTERNS[@]}"; do
    if echo "$COMMAND" | grep -qiE "$pattern"; then
        echo "WARNING: Potentially risky command - proceed with caution" >&2
    fi
done

exit 0