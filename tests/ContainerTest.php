<?php

namespace Neat\Service\Test;

use Neat\Service\Container;
use Neat\Service\NotFoundException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * Test empty
     */
    public function testEmpty()
    {
        $container = new Container();

        $this->assertFalse($container->has(Container::class));
    }

    /**
     * Test service instance has/get/set operations
     */
    public function testInstance()
    {
        $service   = new Service();
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
            return new Service();
        };

        $container = new Container();
        $container->set(Service::class, $closure);

        $this->assertInstanceOf(Service::class, $service1 = $container->get(Service::class));
        $this->assertInstanceOf(Service::class, $service2 = $container->get(Service::class));
        $this->assertNotSame($service1, $service2);

        $factory = $container->factory(Service::class);
        $this->assertTrue(is_callable($factory));
        $this->assertInstanceOf(Service::class, $factory());
    }

    /**
     * Test overwriting services
     */
    public function testOverwrite()
    {
        $service1 = new Service();
        $service2 = new Service();

        $factory1 = function () use ($service1) {
            return $service1;
        };

        $factory2 = function () use ($service2) {
            return $service2;
        };

        foreach ([$service1, $factory1] as $first) {
            foreach ([$service2, $factory2] as $second) {
                $container = new Container();
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
        $container = new Container();

        $this->assertFalse($container->has(Service::class));
    }

    /**
     * Test get unknown service
     */
    public function testGetUnknown()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->get(Service::class);
    }

    /**
     * Test removing a service instance
     */
    public function testRemoveInstance()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->set(Service::class, new Service());
        $container->set(Service::class, null);
        $container->get(Service::class);
    }

    /**
     * Test removing a service factory
     */
    public function testRemoveFactory()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->set(
            Service::class,
            function () {
                return new Service();
            }
        );
        $container->set(Service::class, null);
        $container->get(Service::class);
    }

    /**
     * Test get or create
     */
    public function testGetOrCreate()
    {
        $service = new Service();

        $container = new Container();
        $this->assertInstanceOf(Service::class, $container->getOrCreate(Service::class));

        $container->set(
            Service::class,
            function () use ($service) {
                return $service;
            }
        );
        $this->assertSame($service, $container->getOrCreate(Service::class));
    }

    /**
     * Test sharing a service instance
     */
    public function testShare()
    {
        $closure = function () {
            return new Service();
        };

        $container = new Container();
        $container->set(Service::class, $closure);
        $container->share(Service::class);

        $service1 = $container->get(Service::class);
        $service2 = $container->get(Service::class);
        $this->assertSame($service1, $service2);
    }

    /**
     * Auto-wiring should check if a service is shared and when created should set the instance to the container.
     */
    public function testAutoWiringChecksSharedServices()
    {
        $container = new Container();
        $container->share(Service::class);

        $service1 = $container->getOrCreate(Service::class);
        $service2 = $container->getOrCreate(Service::class);
        $this->assertSame($service1, $service2);
    }

    /**
     * Services that are not shared and are created using auto-wiring should be recreated every call.
     */
    public function testAutoWiringCreateANewInstance()
    {
        $container = new Container();

        $service1 = $container->getOrCreate(Service::class);
        $service2 = $container->getOrCreate(Service::class);
        $this->assertNotSame($service1, $service2);
    }

    /**
     * Calling $container->create() should always return a new instance.
     */
    public function testCallingCreateCreatesANewInstance()
    {
        $container = new Container();
        $container->share(Service::class);

        $service1 = $container->create(Service::class);
        $service2 = $container->create(Service::class);
        $this->assertNotSame($service1, $service2);
    }

    /**
     * Test service provider usage
     */
    public function testServiceProvider()
    {
        $container = new Container();
        $container->register(new ServiceProvider());

        $this->assertFalse($container->has('boolean'));
        $this->assertFalse($container->has('unspecified'));
        $this->assertFalse($container->has('inaccessible'));
        $this->assertFalse($container->has('inappropriate'));
        $this->assertTrue($container->has('db'));
        $this->assertTrue($container->has(Service::class));
        $this->assertInstanceOf(Service::class, $container->get(Service::class));
        $this->assertInstanceOf(Service::class, $container->get('db'));
        $this->assertNotSame(
            $container->get(Service::class),
            $container->get(Service::class)
        );
    }

    /**
     * Test aliases provider
     */
    public function testAliasesProvider()
    {
        $container = new Container();
        $container->register(new AliasesProvider());

        $this->assertSame(Service::class, $container->resolve('svc'));
    }

    /**
     * Test shares provider
     */
    public function testSharesProvider()
    {
        $container = new Container();
        $container->register(new SharesProvider());

        $this->assertSame(
            $container->get(Service::class),
            $container->get(Service::class)
        );
    }

    /**
     * Test alias usage
     */
    public function testAlias()
    {
        $container = new Container();
        $container->alias('db', Service::class);

        $this->assertSame(Service::class, $container->resolve('db'));
    }

    /**
     * Test extending a instance instance
     */
    public function testExtendInstance()
    {
        $original = new Service();
        $extended = new Service();

        $extensionCalls = 0;

        $container = new Container();
        $container->set(Service::class, $original);
        $container->extend(Service::class, function (Service $service) use ($original, $extended, &$extensionCalls): Service {
            $extensionCalls++;

            $this->assertSame($original, $service);

            return $extended;
        });

        $this->assertSame(0, $extensionCalls);
        $this->assertTrue($container->has(Service::class));
        $this->assertSame($extended, $container->get(Service::class));
        $this->assertSame(1, $extensionCalls);
    }

    /**
     * Test extending a service defined by a factory
     */
    public function testExtendFactory()
    {
        $original = new Service();
        $extended = new Service();

        $factoryCalls   = 0;
        $extensionCalls = 0;

        $container = new Container();
        $container->set(Service::class, function () use ($original, &$factoryCalls) {
            $factoryCalls++;

            return $original;
        });
        $container->extend(Service::class, function (Service $service) use ($original, $extended, &$extensionCalls): Service {
            $extensionCalls++;

            $this->assertSame($original, $service);

            return $extended;
        });

        $this->assertSame(0, $factoryCalls);
        $this->assertSame(0, $extensionCalls);
        $this->assertTrue($container->has(Service::class));
        $this->assertSame($extended, $container->get(Service::class));
        $this->assertSame(1, $factoryCalls);
        $this->assertSame(1, $extensionCalls);
    }

    /**
     * Test extending an unknown service
     */
    public function testExtendUnknown()
    {
        $extended = new Service();

        $extensionCalls = 0;

        $container = new Container();
        $container->extend(Service::class, function (Service $service) use ($extended, &$extensionCalls): Service {
            $extensionCalls++;

            $this->assertInstanceOf(Service::class, $service);

            return $extended;
        });

        $this->assertSame(0, $extensionCalls);
        $this->assertFalse($container->has(Service::class));
        $this->assertSame($extended, $container->getOrCreate(Service::class));
        $this->assertSame(1, $extensionCalls);
    }

    /**
     * Test extending a service multiple times
     */
    public function testExtendMultiple()
    {
        $original  = new Service();
        $extended1 = new Service();
        $extended2 = new Service();

        $extension1Calls = 0;
        $extension2Calls = 0;

        $container = new Container();
        $container->set(Service::class, $original);
        $container->extend(Service::class, function (Service $service) use ($extended1, $original, &$extension1Calls): Service {
            $extension1Calls++;

            $this->assertSame($original, $service);

            return $extended1;
        });
        $container->extend(Service::class, function (Service $service) use ($extended2, $extended1, &$extension2Calls): Service {
            $extension2Calls++;

            $this->assertSame($extended1, $service);

            return $extended2;
        });

        $this->assertSame(0, $extension1Calls);
        $this->assertSame(0, $extension2Calls);
        $this->assertTrue($container->has(Service::class));
        $this->assertSame($extended2, $container->get(Service::class));
        $this->assertSame(1, $extension1Calls);
        $this->assertSame(1, $extension2Calls);
    }

    public function testInvokableClass()
    {
        $container = new Container();
        $this->assertSame('test', $container->call(Invokable::class));
    }
}
