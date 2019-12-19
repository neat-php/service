<?php

namespace Neat\Service\Test;

use Neat\Service\Container;
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

class InjectionTest extends TestCase
{
    /**
     * Test without parameters
     */
    public function testWithoutParameters()
    {
        $container = new Container;

        $this->assertInstanceOf(Service::class, $container->create(Service::class));
        $this->assertInstanceOf(Service::class, $container->call(Service::class . '::factory'));
        $this->assertInstanceOf(Service::class, $container->call([Service::class, 'factory']));
        $this->assertSame('result', $container->call(function () {
            return 'result';
        }));
        $this->assertSame(PHP_SAPI, $container->call('php_sapi_name'));
    }

    /**
     * Test object parameter
     */
    public function testObjectParameter()
    {
        $container = new Container;
        $container->set(Service::class, new Service());

        $consumer = $container->create(ServiceConsumer::class);

        $this->assertInstanceOf(ServiceConsumer::class, $consumer);
        $this->assertInstanceOf(Service::class, $consumer->getService());
        $this->assertInstanceOf(Service::class, $container->call(ServiceConsumer::class . '@getService'));
    }

    /**
     * Test object parameter
     */
    public function testNullableObjectParameter()
    {
        $container = new Container;

        $consumer = $container->create(ServiceConsumer::class);

        $this->assertInstanceOf(ServiceConsumer::class, $consumer);
        $this->assertNull($consumer->getService());
    }

    /**
     * Test default parameter value
     */
    public function testDefaultParameterValue()
    {
        $container = new Container;

        $this->assertSame('default', $container->call(function ($default = 'default') {
            return $default;
        }));
    }

    /**
     * Test variadic parameter
     */
    public function testVariadicParameter()
    {
        $container = new Container;
        $arguments = $container->call(function (...$variadic) {
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

        $container = new Container;
        $container->create(__NAMESPACE__ . '\\UnknownClass');
    }

    /**
     * Test unknown function
     */
    public function testUnknownFunction()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container;
        $container->call(__NAMESPACE__ . '\\unknown_function');
    }

    /**
     * Test unknown Method
     */
    public function testUnknownMethod()
    {
        $this->expectException(NotFoundException::class);

        $service = new Service;

        $container = new Container;
        $container->call([$service, 'unknownMethod']);
    }

    /**
     * Test Not found exception for unknown parameter in function
     */
    public function testUnknownParameterInFunction()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Argument not found for parameter $unknown in ' . __NAMESPACE__ . '\\unknown');

        $container = new Container;
        $container->call(__NAMESPACE__ . '\\unknown');
    }

    /**
     * Test Not found exception for unknown parameter in method
     */
    public function testUnknownParameterInMethod()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Argument not found for parameter $unknown in ' . Service::class . '::unknown');

        $service = new Service;

        $container = new Container;
        $container->call([$service, 'unknown']);
    }

    /**
     * Test with arguments
     */
    public function testWithArguments()
    {
        $container = new Container;
        $arguments = ['id' => 1, 'name' => 'john'];

        $idClosure   = function ($id) {
            return $id;
        };
        $nameClosure = function ($name) {
            return $name;
        };

        $this->assertSame('john', $container->call($nameClosure, $arguments));
        $this->assertSame(1, $container->call($idClosure, $arguments));

        $service  = new Service;
        $consumer = $container->create(ServiceConsumer::class, ['service' => $service]);

        $this->assertSame($service, $consumer->getService());
    }
}
