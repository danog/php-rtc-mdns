<?php

namespace Tests\Webrtc\MDNS;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Webrtc\Exception\RuntimeException;
use Webrtc\MDNS\Factory;
use Webrtc\MDNS\MulticastExecutor;

/**
 * Resolution is exercised against a real responder over loopback rather than over the
 * multicast group. What is being tested is the query and response handling, and group
 * delivery is a property of the host: loopback has no MULTICAST flag on most systems, and
 * browsers and systemd-resolved routinely occupy the mDNS port. Only the test that is
 * genuinely about multicast is gated on that.
 */
#[CoversClass(MulticastExecutor::class)]
class MulticastExecutorTest extends TestCase
{
    public function testResolvesANamePublishedByAResponder(): void
    {
        $responder = new MdnsServerMock(['test.local' => '192.168.1.20'], '127.0.0.1:0');
        $address = $responder->start();

        try {
            $this->assertSame('192.168.1.20', (new MulticastExecutor($address))->resolve('test.local'));
        } finally {
            $responder->stop();
        }
    }

    public function testResolvesEachOfSeveralPublishedNames(): void
    {
        $responder = new MdnsServerMock(
            ['a.local' => '10.0.0.1', 'b.local' => '10.0.0.2'],
            '127.0.0.1:0'
        );
        $address = $responder->start();

        try {
            $resolver = new MulticastExecutor($address);

            $this->assertSame('10.0.0.1', $resolver->resolve('a.local'));
            $this->assertSame('10.0.0.2', $resolver->resolve('b.local'));
        } finally {
            $responder->stop();
        }
    }

    /**
     * Under mDNS an absent name is met with silence rather than an error response, so the
     * query has to give up on its own instead of waiting for an answer that never comes.
     */
    public function testGivesUpOnANameNobodyAnswersFor(): void
    {
        $responder = new MdnsServerMock(['test.local' => '192.168.1.20'], '127.0.0.1:0');
        $address = $responder->start();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('DNS query for wrong.local timed out');

            (new MulticastExecutor($address, 0.5))->resolve('wrong.local');
        } finally {
            $responder->stop();
        }
    }

    public function testReachesAResponderOverTheMulticastGroup(): void
    {
        if (!Multicast::isAvailable()) {
            $this->markTestSkipped(Multicast::skipReason());
        }

        $responder = new MdnsServerMock(['test.local' => '192.168.1.20']);
        $responder->start();

        try {
            $this->assertSame('192.168.1.20', (new MulticastExecutor())->resolve('test.local'));
        } finally {
            $responder->stop();
        }
    }

    public function testUsesTheMulticastGroupByDefault(): void
    {
        $this->assertSame('224.0.0.251:5353', Factory::DNS);
    }
}
