<?php

namespace Neat\Service\Test;

use Neat\Service\Container;
use Neat\Service\Injector;
use Neat\Service\NotFoundException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * Test empty
     */
    public function testEmpty()
    {
        $container = new Container;

        $this->assertFalse($container->has(Container::class));
    }

    /**
     * Test injector access
     */
    public function testInjector()
    {
        $container = new Container;
        $this->assertInstanceOf(Injector::class, $container->injector());
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

        $injector = $this->createPartialMock(Injector::class, ['call']);
        $injector
            ->expects($this->exactly(2))
            ->method('call')
            ->with($closure)
            ->willReturnCallback($closure);

        $container = new Container($injector);
        $container->set(Service::class, $closure);

        $this->assertInstanceOf(Service::class, $service1 = $container->get(Service::class));
        $this->assertInstanceOf(Service::class, $service2 = $container->get(Service::class));
        $this->assertNotSame($service1, $service2);
    }

    /**
     * Test overwriting services
     */
    public function testOverwrite()
    {
        $service1 = new Service;
        $service2 = new Service;

        $factory1 = function () use ($service1) {
            return $service1;
        };

        $factory2 = function () use ($service2) {
            return $service2;
        };

        foreach ([$service1, $factory1] as $first) {
            foreach ([$service2, $factory2] as $second) {
                $container = new Container;
                $container->set('service', $first);
                $container->set('service', $second);
                $this->assertSame($service2, $container->get('service'));
            }
        }
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
     * Test get unknown service
     */
    public function testGetUnknown()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container;
        $container->get(Service::class);
    }

    /**
     * Test removing a service instance
     */
    public function testRemoveInstance()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container;
        $container->set(Service::class, new Service);
        $container->set(Service::class, null);
        $container->get(Service::class);
    }

    /**
     * Test removing a service factory
     */
    public function testRemoveFactory()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container;
        $container->set(Service::class, function () { return new Service; });
        $container->set(Service::class, null);
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

        $injector = $this->createPartialMock(Injector::class, ['call']);
        $injector
            ->expects($this->once())
            ->method('call')
            ->with($closure)
            ->willReturn($closure());

        $container = new Container($injector);
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
        $injector = $this
            ->getMockBuilder(Injector::class)
            ->setMethods(['call'])
            ->getMock();

        $injector
            ->expects($this->exactly(2))
            ->method('call')
            ->willReturn(new Service);

        $container = new Container($injector);
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
