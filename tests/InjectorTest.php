<?php

namespace Neat\Service\Test;

use Neat\Service\Container;
use Neat\Service\Injector;
use Neat\Service\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Function with unknown parameter
 *
 * @param mixed $unknown
 */
function unknown($unknown)
{
}

class InjectorTest extends TestCase
{
    /**
     * Test without parameters
     */
    public function testWithoutParameters()
    {
        $injector = new Injector;

        $this->assertInstanceOf(Service::class, $injector->create(Service::class));
        $this->assertInstanceOf(Service::class, $injector->call(Service::class . '::factory'));
        $this->assertInstanceOf(Service::class, $injector->call([Service::class, 'factory']));
        $this->assertSame('result', $injector->call(function () {
            return 'result';
        }));
        $this->assertSame(PHP_SAPI, $injector->call('php_sapi_name'));
    }

    /**
     * Test object parameter
     */
    public function testObjectParameter()
    {
        $injector = new Injector;
        $consumer = $injector->create(ServiceConsumer::class);

        $this->assertInstanceOf(ServiceConsumer::class, $consumer);
        $this->assertInstanceOf(Service::class, $consumer->getService());
        $this->assertInstanceOf(Service::class, $injector->call(ServiceConsumer::class . '@getService'));
    }

    /**
     * Test default parameter value
     */
    public function testDefaultParameterValue()
    {
        $injector = new Injector;

        $this->assertSame('default', $injector->call(function ($default = 'default') {
            return $default;
        }));
    }

    /**
     * Test variadic parameter
     */
    public function testVariadicParameter()
    {
        $injector  = new Injector;
        $arguments = $injector->call(function (...$variadic) {
            return count($variadic);
        });

        $this->assertSame(0, $arguments);
    }

    /**
     * Test unknown class
     */
    public function testUnknownClass()
    {
        $this->expectException(NotFoundException::class);

        $injector = new Injector;
        $injector->create(__NAMESPACE__ . '\\UnknownClass');
    }

    /**
     * Test unknown function
     */
    public function testUnknownFunction()
    {
        $this->expectException(NotFoundException::class);

        $injector = new Injector;
        $injector->call(__NAMESPACE__ . '\\unknown_function');
    }

    /**
     * Test unknown Method
     */
    public function testUnknownMethod()
    {
        $this->expectException(NotFoundException::class);

        $service = new Service;

        $injector = new Injector;
        $injector->call([$service, 'unknownMethod']);
    }

    /**
     * Test Not found exception for unknown parameter in function
     */
    public function testUnknownParameterInFunction()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Argument not found for parameter $unknown in ' . __NAMESPACE__ . '\\unknown');

        $injector = new Injector;
        $injector->call(__NAMESPACE__ . '\\unknown');
    }

    /**
     * Test Not found exception for unknown parameter in method
     */
    public function testUnknownParameterInMethod()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Argument not found for parameter $unknown in ' . Service::class . '::unknown');

        $service = new Service;

        $injector = new Injector;
        $injector->call([$service, 'unknown']);
    }

    /**
     * Test with container
     */
    public function testWithContainer()
    {
        $service = new Service;

        /** @var Container|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->createPartialMock(Container::class, ['has', 'get']);
        $container
            ->expects($this->exactly(2))
            ->method('has')
            ->with(Service::class)
            ->willReturn(true);
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->with(Service::class)
            ->willReturn($service);

        /** @var Container|\PHPUnit_Framework_MockObject_MockObject $unusedContainer */
        $unusedContainer = $this->createPartialMock(Container::class, [
            'has',
            'get'
        ]);
        $unusedContainer
            ->expects($this->never())
            ->method('has');
        $unusedContainer
            ->expects($this->never())
            ->method('get');

        $injector = (new Injector)
            ->withContainer($container)
            ->withContainer($unusedContainer);

        $consumer = $injector->create(ServiceConsumer::class);

        $this->assertInstanceOf(ServiceConsumer::class, $consumer);
        $this->assertSame($service, $consumer->getService());

        $injector = (new Injector)
            ->withContainer($unusedContainer)
            ->withContainer($container, true);

        $consumer = $injector->create(ServiceConsumer::class);

        $this->assertInstanceOf(ServiceConsumer::class, $consumer);
        $this->assertSame($service, $consumer->getService());
    }

    /**
     * Test with arguments
     */
    public function testWithArguments()
    {
        $injector  = new Injector;
        $arguments = ['id' => 1, 'name' => 'john'];

        $idClosure   = function ($id) {
            return $id;
        };
        $nameClosure = function ($name) {
            return $name;
        };

        $this->assertSame('john', $injector->call($nameClosure, $arguments));
        $this->assertSame(1, $injector->call($idClosure, $arguments));

        $service  = new Service;
        $consumer = $injector->create(ServiceConsumer::class, ['service' => $service]);

        $this->assertSame($service, $consumer->getService());
    }
}
