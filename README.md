# Payum Lock Request Extension

### A Payum extension providing the ability to lock concurrent requests

[![Tests](https://github.com/webgriffe/PayumLockRequestExtension/actions/workflows/test.yml/badge.svg?branch=master)](https://github.com/webgriffe/PayumLockRequestExtension/actions/workflows/test.yml)

This extension provides the ability to lock concurrent requests to a Payum gateway (for example when the PSP send both a
notify action and a traditional capture action in the same moment). It
uses [Symfony Lock Component](https://symfony.com/doc/current/components/lock.html) to provide a simple and reliable
locking mechanism.

## Installation

```bash
composer require webgriffe/payum-lock-request-extension
```

## Usage

```php
<?php

// Use your preferred \Symfony\Component\Lock\PersistingStoreInterface implementation
$persistingStore = new Symfony\Component\Lock\Store\SemaphoreStore();

$lockFactory = new \Symfony\Component\Lock\LockFactory($persistingStore);

$lockRequestExtension = new \Webgriffe\PayumLockRequestExtension\LockRequestExtension(
    $lockFactory,
    'my_lock_prefix', // Optional, default value is 'webgriffe_payum_lock_request_extension'
    30.0, // Optional, default value is 30.0
    true // Optional, default value is true
);

/** @var \Payum\Core\Gateway $gateway */
$gateway->addExtension($lockRequestExtension);

// here the extension will be called to wrap the execute acton in a lock
$gateway->execute(new FooRequest);

```

## Configuration

The extension can be configured with the following options:

- Lock prefix: to ensure lock key is unique, default is `webgriffe_payum_lock_request_extension`.
- Lock TTL: the maximum time in seconds that a lock can be hold, default is `30`.
- Lock autorelease: release the lock or not when the lock instance is destroyed, default is `true`.
