<?php

namespace Tests\Webrtc\MDNS;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use React\Dns\RecordNotFoundException;
use Webrtc\MDNS\Factory;
use PHPUnit\Framework\TestCase;
use Webrtc\MDNS\MulticastExecutor;
use function React\Async\async;
use function React\Async\await;
use function React\Async\delay;

#[UsesClass(MulticastExecutor::class)]
#[CoversClass(Factory::class)]
class FactoryTest extends TestCase
{
    public function testCreate(){
        $this->assertTrue(true);
    }
    public function testSuccessfulMulticastDns()
    {
        $mdnsMock = new MdnsServerMock(['test.local' => '192.168.1.20']);
        $mdnsMock->start();

        $factory = new Factory();
        $resolver = $factory->createResolver();
        $ip = await($resolver->resolve('test.local'));

        $this->assertSame('192.168.1.20', $ip);

        $mdnsMock->stop();
    }
    public function testFailedMulticastDns()
    {
        $mdnsMock = new MdnsServerMock(['test.local' => '192.168.1.20']);
        $mdnsMock->start();

        async(function () use ($mdnsMock){
            delay(.2);
            $mdnsMock->stop();
        })();

        $factory = new Factory();
        $resolver = $factory->createResolver();
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionMessage('DNS query for wrong.local (A) returned an error response (Non-Existent Domain / NXDOMAIN)');
        await($resolver->resolve('wrong.local'));
    }
//
//    public function testCreateResolver()
//    {
//        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
//        $factory = new Factory($loop);
//
//        $resolver = $factory->createResolver();
//
//        $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
//        $loop->stop();
//    }
//
//    /**
//     * @throws \ReflectionException
//     */
//    public function testConstructWithoutLoopAssignsLoopAutomatically()
//    {
//        $factory = new Factory();
//
//        $ref = new ReflectionProperty($factory, 'loop');
//        $loop = $ref->getValue($factory);
//
//        $this->assertInstanceOf('React\EventLoop\LoopInterface', $loop);
//        $loop->stop();
//
//    }
}
