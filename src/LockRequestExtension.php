<?php

declare(strict_types=1);

namespace Webgriffe\PayumLockRequestExtension;

use InvalidArgumentException;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Model\PayoutInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\IdentityInterface;
use RuntimeException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

final class LockRequestExtension implements ExtensionInterface
{
    private const LOCK_TTL = 30.0;

    private const LOCK_PREFIX = 'webgriffe_payum_lock_request_extension';

    public function __construct(
        private LockFactory $lockFactory,
        private string $lockPrefix = self::LOCK_PREFIX,
        private float $lockTTL = self::LOCK_TTL,
        private bool $lockAutoRelease = true,
    ) {
    }

    private ?LockInterface $lock = null;

    public function onPreExecute(Context $context): void
    {
        $request = $context->getRequest();
        if (! $request instanceof Generic) {
            // TODO log here
            return;
        }
        if (null !== $this->lock) {
            return;
        }

        // Try to retrieve the payment unique key, if not found maybe we can skip the lock
        try {
            $paymentKey = $this->getPaymentKeyFromRequest($request);
        } catch (RuntimeException $e) {
            // TODO log here
            return;
        }

        // Create the lock for the current payment
        $this->lock = $this->lockFactory->createLock(
            $paymentKey,
            $this->lockTTL,
            $this->lockAutoRelease,
        );

        // Try to acquire the lock
        if (! $this->lock->acquire(true)) {
            // Lock acquisition failed, so we throw an exception. Should this be handled better by displaying a message?
            $this->lock = null;

            throw new RuntimeException('Cannot acquire lock for current payment.');
        }
    }

    public function onExecute(Context $context): void
    {
    }

    public function onPostExecute(Context $context): void
    {
        // run only for first level (last execution in stack)
        if ($context->getPrevious() !== []) {
            return;
        }

        if ($this->lock) {
            $this->lock->release();
            $this->lock = null;
        }
    }

    /**
     * @throws RuntimeException
     */
    private function getPaymentKeyFromRequest(Generic $request): string
    {
        $model = $request->getModel();

        if ($model instanceof TokenInterface) {
            $details = $model->getDetails();

            return $this->getPaymentKeyFromClassAndId($details->getClass(), $details->getId());
        }
        if ($model instanceof IdentityInterface) {
            return $this->getPaymentKeyFromClassAndId($model->getClass(), $model->getId());
        }

        $firstModel = $request->getFirstModel();

        if (($firstModel instanceof PaymentInterface || $firstModel instanceof PayoutInterface) &&
            method_exists($firstModel, 'getId')
        ) {
            return $this->getPaymentKeyFromClassAndId(get_class($firstModel), $firstModel->getId());
        }

        throw new RuntimeException(sprintf('Cannot extract payment key from current request "%s".', get_debug_type($request)));
    }

    private function getPaymentKeyFromClassAndId(string $class, mixed $id): string
    {
        if (! is_scalar($id)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot extract payment key from class "%s" and id "%s".',
                $class,
                get_debug_type($id),
            ));
        }

        return sprintf(
            '%s_%s#%s',
            rtrim($this->lockPrefix, '_'),
            $class,
            (string) $id,
        );
    }
}
