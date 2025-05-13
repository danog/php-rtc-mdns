<?php

namespace Tests\Webrtc\MDNS;

use PHPUnit\Framework\Attributes\CoversClass;
use React\Dns\Protocol\BinaryDumper;
use React\Dns\Protocol\Parser;
use React\Dns\Query\Query;
use ReflectionProperty;
use Webrtc\MDNS\Factory;
use Webrtc\MDNS\MulticastExecutor;

#[CoversClass(MulticastExecutor::class)]
class MulticastExecutorTest extends BaseTestCase
{
    public function testQueryWillReturnPromise()
    {
        $nameserver = Factory::DNS;
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $parser = new Parser();
        $dumper = new BinaryDumper();
        $sockets = new \React\Datagram\Factory();

        $executor = new MulticastExecutor($nameserver, $loop, $parser, $dumper, 5, $sockets);

        $query = new Query('name', 'type', 'class');

        $ret = $executor->query($query);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $ret);
        $loop->stop();
        $executor->getConn()->close();
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstructWithoutLoopAssignsLoopAutomatically()
    {
        $executor = new MulticastExecutor();

        $ref = new ReflectionProperty($executor, 'loop');
        $loop = $ref->getValue($executor);

        $this->assertInstanceOf('React\EventLoop\LoopInterface', $loop);
    }

    public function testCancellingPromiseWillCloseSocketAndReject()
    {
        $nameserver = Factory::DNS;
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $parser = new Parser();
        $dumper = new BinaryDumper();
        $sockets = new \React\Datagram\Factory();

        $executor = new MulticastExecutor($nameserver, $loop, $parser, $dumper, 5, $sockets);

        // prefer newer EventLoop 1.0/0.5+ TimerInterface or fall back to legacy namespace
        $timer = $this->getMockBuilder(
            interface_exists('React\EventLoop\TimerInterface') ? 'React\EventLoop\TimerInterface' : 'React\EventLoop\Timer\TimerInterface'
        )->getMock();

        $loop->expects($this->once())->method('addTimer')->willReturn($timer);
        $loop->expects($this->once())->method('cancelTimer')->with($timer);


        $query = new Query('name', 'type', 'class');

        $ret = $executor->query($query);
        $this->assertInstanceOf('React\Promise\Promise', $ret);

        $ret->cancel();

        $ret->then($this->expectCallableNever(), $this->expectCallableOnceWith($this->isInstanceOf('RuntimeException')));
        $loop->stop();
    }
}
