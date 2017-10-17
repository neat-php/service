<?php namespace Phrodo\Application;

/**
 * Application exception class
 */
class NotFoundException extends \Exception
{
    /**
     * Create NotFoundException for parameter
     *
     * @param \ReflectionParameter|string        $parameter
     * @param \ReflectionFunctionAbstract|string $function
     * @return static
     */
    public static function forParameter($parameter, $function)
    {
        if ($parameter instanceof \ReflectionParameter) {
            $parameter = $parameter->getName();
        }

        if ($function instanceof \ReflectionMethod) {
            $function = $function->class . '::' . $function->name;
        } elseif ($function instanceof \ReflectionFunctionAbstract) {
            $function = $function->name;
        }

        return new static('Argument not found for parameter $' . $parameter . ' in ' . $function);
    }
}
