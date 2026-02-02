# Events Photo share backend app

## description

- this app is a series of API endpoints to serve a Wordpress frontend app data from a mysql database or S3 bucket
- the purpose of the app is that for an event, a customer can save some pictures and retrieve them, in a interface
    - other people are able to see the images, based on a uuid, and they can also add/delete images
        - the people entering with the uuid url, can only delete their added images (this is done via a hash saved as a
          cookie in clients browser)
- the customer (role_client) can delete all the images from all other users
- the files(pictures and videos) are stored in s3 having this structure
    - order_id
        - client
        - hash1
        - hash2
- the customer can also add a title to the event

## flows

1. order create
    - when an order is placed, the frontend component, will call the following api
        - create a user (if it is the first order on the account)
        - create an event (for that order)

2. event edit
    - the client can access his events via WP orders, and edit them accordingly(only if it is logged in a WP account)
        - can add/change name of the event
        - can add/delete all the images
3. images add/delete for guests
    - any user with the link and uuid can add/delete images(but only their own)

# Technical

## Overview

- use symfony best practices
- created_at and modified_at want to be set at the database level, use the doctrine annotations to acheive that, i dont
  want them to be set in code
- use chaining of method calls as shown bellow

```php
        $user->setEmail($userAddDto->email->value)
            ->setRole($userAddDto->role)
            ->setCreatedAt($now)
            ->setModifiedAt($now);
```

## Exceptions

- all exceptions should have a specific folder, and a base exception in that folder
    - all other exceptions that are related to the concept, should extend base exception and be placed in the specific
      folder

## Naming

- names should be explicit, instead of \$repo use full name \$productRepository

## Tests

- use Data provides whenever possible to reduce code
- 80% code coverage for services is advisable
- services should be written in such a manner that they can be tested easily 