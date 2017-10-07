<?php namespace Phrodo\Application\Test;

use PHPUnit\Framework\TestCase;
use Phrodo\Application\Container;
use Phrodo\Application\Dispatcher;
use Phrodo\Application\NotFoundException;

class ContainerTest extends TestCase
{
    /**
     * Test initialization
     */
    public function testInitialization()
    {
        $container = new Container;

        $this->assertSame($container, $container->get(Container::class));
        $this->assertSame($container, $container->get('container'));
    }

    /**
     * Test invocation
     */
    public function testInvocation()
    {

        $closure = function () {};
        $service = new Service;

        $dispatcher = $this->createPartialMock(Dispatcher::class, ['call']);
        $dispatcher
            ->expects($this->once())
            ->method('call')
            ->with($closure)
            ->willReturn($service);

        $container = new Container($dispatcher);

        $this->assertSame($service, $container($closure));
    }

    /**
     * Test dispatcher access
     */
    public function testDispatch()
    {
        $container = new Container;
        $this->assertInstanceOf(Dispatcher::class, $container->dispatch());
    }

    /**
     * Test service instance has/get/set operations
     */
    public function testInstance()
    {
        $service   = new Service;
        $container = new Container();
        $container->set(Service::class, $service);

        $this->assertSame($service, $container->get(Service::class));
    }

    /**
     * Test service factory has/get/set operations
     */
    public function testFactory()
    {
        $closure = function () {
            return new Service;
        };

        $dispatcher = $this->createPartialMock(Dispatcher::class, ['call']);
        $dispatcher
            ->expects($this->exactly(2))
            ->method('call')
            ->with($closure)
            ->willReturnCallback($closure);

        $container = new Container($dispatcher);
        $container->set(Service::class, $closure);

        $this->assertInstanceOf(Service::class, $service1 = $container->get(Service::class));
        $this->assertInstanceOf(Service::class, $service2 = $container->get(Service::class));
        $this->assertNotSame($service1, $service2);
    }

    /**
     * Test has unknown service
     */
    public function testHasUnknown()
    {
        $container = new Container;

        $this->assertFalse($container->has(Service::class));
    }

    /**
     * Test get unknown class
     */
    public function testGetUnknown()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container;
        $container->get(Service::class);
    }

    /**
     * Test sharing a service instance
     */
    public function testShare()
    {
        $closure = function () {
            return new Service;
        };

        $dispatcher = $this->createPartialMock(Dispatcher::class, ['call']);
        $dispatcher
            ->expects($this->once())
            ->method('call')
            ->with($closure)
            ->willReturn($closure());

        $container = new Container($dispatcher);
        $container->set(Service::class, $closure);
        $container->share(Service::class);

        $service1 = $container->get(Service::class);
        $service2 = $container->get(Service::class);
        $this->assertSame($service1, $service2);
    }

    /**
     * Test service provider usage
     */
    public function testServiceProvider()
    {
        $dispatcher = $this
            ->getMockBuilder(Dispatcher::class)
            ->setMethods(['call'])
            ->getMock();

        $dispatcher
            ->expects($this->exactly(2))
            ->method('call')
            ->willReturn(new Service);

        $container = new Container($dispatcher);
        $container->register(new ServiceProvider);

        $this->assertFalse($container->has('boolean'));
        $this->assertFalse($container->has('unspecified'));
        $this->assertFalse($container->has('inaccessible'));
        $this->assertFalse($container->has('inappropriate'));
        $this->assertTrue($container->has('db'));
        $this->assertTrue($container->has(Service::class));
        $this->assertInstanceOf(Service::class, $container->get(Service::class));
        $this->assertInstanceOf(Service::class, $container->get('db'));
    }

    /**
     * Test alias usage
     */
    public function testAlias()
    {
        $container = new Container;
        $container->alias('db', Service::class);

        $this->assertSame(Service::class, $container->resolve('db'));
    }
}
