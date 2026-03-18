#!/bin/bash
# Validates SQL queries - allows only SELECT for read-only access
# Use for db-reader type agents

INPUT=$(cat)
COMMAND=$(echo "$INPUT" | jq -r '.tool_input.command // empty')

if [ -z "$COMMAND" ]; then
    exit 0
fi

# Block SQL write operations (case-insensitive)
if echo "$COMMAND" | grep -iE '\b(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE|REPLACE|MERGE|GRANT|REVOKE)\b' > /dev/null; then
    echo "BLOCKED: Write operations not allowed. Use SELECT queries only." >&2
    exit 2
fi

exit 0