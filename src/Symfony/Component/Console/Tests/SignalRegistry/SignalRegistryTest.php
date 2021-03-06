<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\SignalRegistry;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\SignalRegistry\SignalRegistry;

/**
 * @requires extension pcntl
 */
class SignalRegistryTest extends TestCase
{
    public function tearDown(): void
    {
        pcntl_async_signals(false);
        pcntl_signal(SIGUSR1, SIG_DFL);
        pcntl_signal(SIGUSR2, SIG_DFL);
    }

    public function testOneCallbackForASignal_signalIsHandled()
    {
        $signalRegistry = new SignalRegistry();

        $isHandled = false;
        $signalRegistry->register(SIGUSR1, function () use (&$isHandled) {
            $isHandled = true;
        });

        posix_kill(posix_getpid(), SIGUSR1);

        $this->assertTrue($isHandled);
    }

    public function testTwoCallbacksForASignal_bothCallbacksAreCalled()
    {
        $signalRegistry = new SignalRegistry();

        $isHandled1 = false;
        $signalRegistry->register(SIGUSR1, function () use (&$isHandled1) {
            $isHandled1 = true;
        });

        $isHandled2 = false;
        $signalRegistry->register(SIGUSR1, function () use (&$isHandled2) {
            $isHandled2 = true;
        });

        posix_kill(posix_getpid(), SIGUSR1);

        $this->assertTrue($isHandled1);
        $this->assertTrue($isHandled2);
    }

    public function testTwoSignals_signalsAreHandled()
    {
        $signalRegistry = new SignalRegistry();

        $isHandled1 = false;
        $isHandled2 = false;

        $signalRegistry->register(SIGUSR1, function () use (&$isHandled1) {
            $isHandled1 = true;
        });

        posix_kill(posix_getpid(), SIGUSR1);

        $this->assertTrue($isHandled1);
        $this->assertFalse($isHandled2);

        $signalRegistry->register(SIGUSR2, function () use (&$isHandled2) {
            $isHandled2 = true;
        });

        posix_kill(posix_getpid(), SIGUSR2);

        $this->assertTrue($isHandled2);
    }

    public function testTwoCallbacksForASignal_previousAndRegisteredCallbacksWereCalled()
    {
        $signalRegistry = new SignalRegistry();

        $isHandled1 = false;
        pcntl_signal(SIGUSR1, function () use (&$isHandled1) {
            $isHandled1 = true;
        });

        $isHandled2 = false;
        $signalRegistry->register(SIGUSR1, function () use (&$isHandled2) {
            $isHandled2 = true;
        });

        posix_kill(posix_getpid(), SIGUSR1);

        $this->assertTrue($isHandled1);
        $this->assertTrue($isHandled2);
    }

    public function testTwoCallbacksForASignal_previousCallbackFromAnotherRegistry()
    {
        $signalRegistry1 = new SignalRegistry();

        $isHandled1 = false;
        $signalRegistry1->register(SIGUSR1, function () use (&$isHandled1) {
            $isHandled1 = true;
        });

        $signalRegistry2 = new SignalRegistry();

        $isHandled2 = false;
        $signalRegistry2->register(SIGUSR1, function () use (&$isHandled2) {
            $isHandled2 = true;
        });

        posix_kill(posix_getpid(), SIGUSR1);

        $this->assertTrue($isHandled1);
        $this->assertTrue($isHandled2);
    }
}
