# 🚨 VERY IMPORTANT: Testing Principles

## Core Testing Philosophy

### ❌ NEVER: Write Tests That Accommodate Bad Code
- **NEVER** write tests that work around or accommodate incorrect behavior in application code
- **NEVER** adjust test expectations to match buggy or non-standard code behavior
- **NEVER** accept incorrect exit codes, missing error handling, or improper return values

### ✅ ALWAYS: Fix Application Code to Meet Test Expectations
- **ALWAYS** fix the application code when tests reveal problems
- **ALWAYS** ensure proper exit codes (0 for success, non-zero for errors)
- **ALWAYS** implement proper error handling and return values
- **ALWAYS** follow standard conventions and best practices

## Test-Driven Development (TDD) Approach

### The Correct Process:
1. **Write tests that define correct behavior** (proper exit codes, error handling, etc.)
2. **Run tests and let them fail** if code doesn't meet expectations
3. **Fix the application code** to make tests pass
4. **Refactor and improve** while maintaining test coverage

### Example: Console Commands
```php
// ❌ WRONG: Adjusting test to accommodate bad code
public function test_command_handles_invalid_user(): void
{
    $exitCode = Artisan::call('command', ['--user' => 'invalid']);
    $this->assertEquals(0, $exitCode); // Accepting wrong behavior
}

// ✅ CORRECT: Expect proper behavior and fix code if needed
public function test_command_handles_invalid_user(): void
{
    $exitCode = Artisan::call('command', ['--user' => 'invalid']);
    $this->assertEquals(1, $exitCode); // Expecting proper error exit code
}
```

## Benefits of This Approach
- **Code Quality**: Forces adherence to standards and best practices
- **Maintainability**: Prevents accumulation of technical debt
- **Reliability**: Ensures proper error handling and edge cases
- **Documentation**: Tests serve as executable documentation of correct behavior

## When Tests Fail
1. **First**: Analyze if the failure indicates a real problem in the code
2. **If yes**: Fix the application code, don't adjust the test
3. **If no**: Only then consider if the test expectation is incorrect

## Remember
> "Tests should drive code quality improvement, not accommodate poor code quality."
