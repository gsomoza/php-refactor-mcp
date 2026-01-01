# Test Fixtures

This directory contains fixtures for testing refactoring tools. Each subdirectory corresponds to a specific tool.

## Structure

```
tests/Tools/Fixtures/
├── RenameVariableTool/
│   ├── rename_in_function.php
│   ├── rename_in_method.php
│   └── ...
├── ExtractMethodTool/
│   ├── extract_simple_method.php
│   └── ...
├── IntroduceVariableTool/
│   └── ...
└── ExtractVariableTool/
    └── ...
```

## Fixture Format

Each fixture is a PHP file with:
1. Test parameters in a docblock comment at the top
2. The PHP code to be tested

### Example

```php
<?php
/**
 * @selectionRange: 3
 * @oldName: $oldVar
 * @newName: $newVar
 */

function test() {
    $oldVar = 1;
    $result = $oldVar + 2;
    return $result;
}
```

## Parameter Formats

All tools now use normalized parameter names for consistency:

### RenameVariableTool
```php
/**
 * @selectionRange: {line_number}
 * @oldName: {variable_name}
 * @newName: {new_variable_name}
 */
```

### ExtractMethodTool
```php
/**
 * @selectionRange: {start_line}-{end_line}
 * @methodName: {method_name}
 */
```

### IntroduceVariableTool / ExtractVariableTool
```php
/**
 * @selectionRange: {line}:{column}
 * @variableName: {variable_name}
 */
```

Note: All range/position parameters are now consistently named `selectionRange`.

## Adding a New Fixture

1. Create a new `.php` file in the appropriate tool directory
2. Add a docblock comment with parameters at the top
3. Add the PHP code to test (starting after the docblock)
4. Run tests with `UPDATE_SNAPSHOTS=true composer test` to generate snapshots

**Important:** Line/position numbers should reference the line numbers in the *clean* code (without the docblock). The test framework automatically strips the docblock before processing.

## How Tests Work

1. **Discovery**: Test framework automatically finds all `.php` files in fixture directories
2. **Parsing**: Extracts parameters from docblock annotations
3. **Stripping**: Removes the docblock from the code
4. **Execution**: Runs the tool with the clean code and parsed parameters
5. **Validation**: Compares output against saved snapshots

## Snapshots

Snapshots are stored in `tests/Tools/__snapshots__/` and are automatically created/updated when tests run with `UPDATE_SNAPSHOTS=true`.

Each fixture generates a corresponding snapshot file containing the expected refactored output (without any docblock comments).
