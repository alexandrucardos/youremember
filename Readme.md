# API for a profile of a deceased person

## Description

The purpose of this is to learn some **Domain Driven** good practices.

It uses **Symfony structure along with some DDD** (Domain, Application).

**Application** contains all the "actions" that the API is capable of, and **Domain** is responsible for the bussines
logic.

### Domain:

#### Entities

- business entities that should contain all the business related logic (NOT the same with Doctrine Entities)

#### Exceptions:

- ones that are unrecoverable for the user (**Exception** folder)
- ones that are recoverable (**Messages** folder)

#### ValueObject

- business specific elements, with self validation

### Application:

#### Commands(and handlers):

- for adding and changing data

#### Query:

- for retrieving data

### Infrastructure:

All the other folders in /src, basically Symfony structure

## Security:

- security is done via a Symfony subscriber (\App\EventSubscriber\SecurityValidationRequestSubscriber)

## Logging:

- the logs are saved in files with a 30 days rotation using Monolog via subscribers also

## Database:

- MySql connection, and operations, done via doctrine/doctrine-bundle

## Translation

- done using symfony/translation

# Automation

- for automation, you can use the following repo, although it defies the purpose of learning it is useful for speeding
  things up

```sh
npx github:Lexus2016/claude-code-studio
```