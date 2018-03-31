<?php
namespace Neat\Service;

use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

/**
 * Not found exception
 */
class NotFoundException extends \Exception
{
    /**
     * Create NotFoundException for parameter
     *
     * @param ReflectionParameter|string                 $parameter
     * @param ReflectionFunction|ReflectionMethod|string $function
     * @return static
     */
    public static function forParameter($parameter, $function)
    {
        if ($parameter instanceof ReflectionParameter) {
            $parameter = $parameter->getName();
        }
        if ($function instanceof ReflectionMethod) {
            $function = $function->class . '::' . $function->name;
        }
        if ($function instanceof ReflectionFunction) {
            $function = $function->name;
        }

        return new static('Argument not found for parameter $' . $parameter . ' in ' . $function);
    }

    /**
     * Create NotFoundException for another exception
     *
     * @param Throwable $exception
     * @return static
     */
    public static function forException(Throwable $exception)
    {
        return new static($exception->getMessage(), $exception->getCode(), $exception);
    }
}
