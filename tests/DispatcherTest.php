<?php namespace Phrodo\Application\Test;

use PHPUnit\Framework\TestCase;
use Phrodo\Application\Container;
use Phrodo\Application\Dispatcher;
use Phrodo\Application\NotFoundException;

class DispatcherTest extends TestCase
{
    /**
     * Test without parameters
     */
    public function testWithoutParameters()
    {
        $dispatcher = new Dispatcher;

        $this->assertInstanceOf(Service::class, $dispatcher->create(Service::class));
        $this->assertSame('result', $dispatcher->call(function () {
            return 'result';
        }));
        $this->assertSame(PHP_SAPI, $dispatcher->call('php_sapi_name'));
    }

    /**
     * Test object parameter
     */
    public function testObjectParameter()
    {
        $dispatcher = new Dispatcher;
        $consumer   = $dispatcher->create(ServiceConsumer::class);

        $this->assertInstanceOf(ServiceConsumer::class, $consumer);
        $this->assertInstanceOf(Service::class, $consumer->getService());
        $this->assertInstanceOf(Service::class, $dispatcher->call(ServiceConsumer::class . '@getService'));
    }

    /**
     * Test default parameter value
     */
    public function testDefaultParameterValue()
    {
        $dispatcher = new Dispatcher;

        $this->assertSame('default', $dispatcher->call(function ($default = 'default') {
            return $default;
        }));
    }

    /**
     * Test variadic parameter
     */
    public function testVariadicParameter()
    {
        $dispatcher = new Dispatcher;
        $arguments  = $dispatcher->call(function (...$variadic) {
            return count($variadic);
        });

        $this->assertSame(0, $arguments);
    }

    /**
     * Test unknown parameter
     */
    public function testUnknownParameterInClosure()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Argument not found for parameter $unknown');

        $dispatcher = new Dispatcher;
        $dispatcher->call(function ($unknown) {
        });
    }

    /**
     * Test unknown parameter in method
     */
    public function testUnknownParameterInMethod()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Argument not found for parameter $unknown');

        $object = new class
        {
            /**
             * Method with unknown parameter
             *
             * @param mixed $unknown
             */
            function method($unknown)
            {
            }
        };

        $dispatcher = new Dispatcher;
        $dispatcher->call([$object, 'method']);
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

        $dispatcher = (new Dispatcher)
            ->withContainer($container)
            ->withContainer($unusedContainer);

        $consumer = $dispatcher->create(ServiceConsumer::class);

        $this->assertInstanceOf(ServiceConsumer::class, $consumer);
        $this->assertSame($service, $consumer->getService());

        $dispatcher = (new Dispatcher)
            ->withContainer($unusedContainer)
            ->withContainer($container, true);

        $consumer = $dispatcher->create(ServiceConsumer::class);

        $this->assertInstanceOf(ServiceConsumer::class, $consumer);
        $this->assertSame($service, $consumer->getService());
    }

    /**
     * Test with arguments
     */
    public function testWithArguments()
    {
        $dispatcher = new Dispatcher;
        $arguments  = ['id' => 1, 'name' => 'john'];

        $idClosure = function ($id) {
            return $id;
        };
        $nameClosure = function ($name) {
            return $name;
        };

        $this->assertSame('john', $dispatcher->call($nameClosure, $arguments));
        $this->assertSame(1, $dispatcher->call($idClosure, $arguments));

        $service  = new Service;
        $consumer = $dispatcher->create(ServiceConsumer::class, ['service' => $service]);

        $this->assertSame($service, $consumer->getService());
    }

    /**
     * Test with namespace
     */
    public function testWithNamespace()
    {
        $dispatcher = (new Dispatcher)->withNamespace(__NAMESPACE__);

        $this->assertInstanceOf(Service::class, $dispatcher->create('Service'));
        $this->assertInstanceOf(Service::class, $dispatcher->call('ServiceConsumer@getService'));
        $this->assertInstanceOf(ServiceConsumer::class, $dispatcher->call('ServiceConsumer::create'));
    }
}
