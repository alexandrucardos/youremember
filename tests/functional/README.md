# Functional Tests Infrastructure

This directory contains the functional tests infrastructure using SQLite for fast, isolated database testing.

## Overview

The functional test infrastructure provides:

- SQLite-based in-memory database for fast test execution
- Automatic database schema creation and cleanup
- Base test class with common utilities
- Isolated test environment

## Base Test Class

All functional tests should extend `FunctionalTestBase` which provides:

### Automatic Setup

- Creates a fresh database schema before each test
- Provides a configured `KernelBrowser` client
- Provides `EntityManager` for database operations

### Automatic Teardown

- Cleans all database tables after each test
- Closes entity manager properly

### Helper Methods

- `generateValidToken(string $email): string` - Generates a valid authentication token
- `generateExpiredToken(string $email): string` - Generates an expired authentication token

## Creating a Functional Test

```php
<?php

declare(strict_types = 1);

namespace App\Tests\functional\Controller\API;

use App\Domain\ValueObject\UserRole;use App\Entity\User;use App\Tests\functional\FunctionalTestBase;

class MyControllerTest extends FunctionalTestBase
{
    public function testMyEndpoint(): void
    {
        // Create test data
        $user = new User();
        $user->setEmail('test@example.com')
            ->setRole(UserRole::ROLE_CLIENT);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Make request
        $this->client->request(
            'GET',
            '/api/my-endpoint',
            [],
            [],
            ['HTTP_TOKEN' => $this->generateValidToken('test@example.com')]
        );

        // Assert response
        self::assertResponseIsSuccessful();
    }
}
```

## Database Configuration

The test environment uses SQLite as configured in:

- `.env.test` - Contains `DATABASE_URL` pointing to SQLite
- `config/packages/test/doctrine.yaml` - Doctrine configuration for test environment

## Running Tests

Run all functional tests:

```bash
vendor/bin/phpunit tests/functional
```

Run specific test class:

```bash
vendor/bin/phpunit tests/functional/Controller/API/EventControllerTest.php
```

Run specific test method:

```bash
vendor/bin/phpunit --filter testCreateEventWithValidTokenAndExistingUser
```

## Best Practices

1. **Isolation**: Each test should be independent and not rely on other tests
2. **Clean Data**: Use the base class setup/teardown - don't create your own
3. **Explicit Setup**: Create all required test data explicitly in each test
4. **Assertions**: Always verify the expected behavior with assertions
5. **Database Access**: Use `$this->entityManager` for all database operations

## SQLite Specifics

SQLite has some limitations compared to MySQL/PostgreSQL:

- No `ON UPDATE CURRENT_TIMESTAMP` support (handled in application code)
- Foreign key constraints must be explicitly enabled
- Some data types are emulated

The base class handles these differences automatically.
