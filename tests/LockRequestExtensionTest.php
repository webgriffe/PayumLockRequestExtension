<?php

declare(strict_types=1);

namespace Tests\Webgriffe\PayumLockRequestExtension;

use Payum\Core\Extension\Context;
use Payum\Core\Gateway;
use Payum\Core\Model\Identity;
use Payum\Core\Model\Token;
use Payum\Core\Request\Capture;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Webgriffe\PayumLockRequestExtension\LockRequestExtension;

class LockRequestExtensionTest extends TestCase
{
    private LockRequestExtension $extension;

    private InMemoryStore $lockStore;

    protected function setUp(): void
    {
        $this->lockStore = new InMemoryStore();
        $this->extension = new LockRequestExtension(
            new LockFactory($this->lockStore),
        );
    }

    public function test_it_locks_requests(): void
    {
        $context = $this->createContext();
        $this->extension->onPreExecute($context);

        self::assertTrue($this->lockStore->exists(new Key('webgriffe_payum_lock_request_extension_PaymentClass#5')));

        $this->extension->onPostExecute($context);

        self::assertFalse($this->lockStore->exists(new Key('webgriffe_payum_lock_request_extension_PaymentClass#5')));
    }

    private function createContext(): Context
    {
        $paymentToken = new Token();
        $paymentToken->setDetails(new Identity(5, 'PaymentClass'));
        return new Context(
            new Gateway(),
            new Capture($paymentToken),
            [],
        );
    }
}
