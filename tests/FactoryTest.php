<?php

namespace Tests\Webrtc\MDNS;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Webrtc\Exception\RuntimeException;
use Webrtc\MDNS\Factory;
use Webrtc\MDNS\MulticastExecutor;

#[UsesClass(MulticastExecutor::class)]
#[CoversClass(Factory::class)]
class FactoryTest extends TestCase
{
    public function testCreateResolver(): void
    {
        $this->assertInstanceOf(MulticastExecutor::class, (new Factory())->createResolver());
    }

    public function testUsesAGivenExecutor(): void
    {
        $executor = new MulticastExecutor();

        $this->assertSame($executor, (new Factory($executor))->createResolver());
    }

    public function testSuccessfulMulticastDns(): void
    {
        if (!Multicast::isAvailable()) {
            $this->markTestSkipped(Multicast::skipReason());
        }

        $responder = new MdnsServerMock(['test.local' => '192.168.1.20']);
        $responder->start();

        try {
            $resolver = (new Factory())->createResolver();

            $this->assertSame('192.168.1.20', $resolver->resolve('test.local'));
        } finally {
            $responder->stop();
        }
    }

    public function testFailedMulticastDns(): void
    {
        if (!Multicast::isAvailable()) {
            $this->markTestSkipped(Multicast::skipReason());
        }

        $responder = new MdnsServerMock(['test.local' => '192.168.1.20']);
        $responder->start();

        try {
            $resolver = (new Factory(new MulticastExecutor(Factory::DNS, 0.5)))->createResolver();

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('DNS query for wrong.local timed out');

            $resolver->resolve('wrong.local');
        } finally {
            $responder->stop();
        }
    }
}
