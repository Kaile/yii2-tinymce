<?php

namespace moonland\tinymce\tests;

use Closure;
use ReflectionClass;

trait ReflectionHelper
{
    protected static function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflector = new ReflectionClass($object);
        $property = $reflector->getProperty($propertyName);
        
        $getter = function () use ($property, $object) {
            $prop = $property;
            return $prop->getValue($object);
        };
        
        return Closure::bind($getter, null, $object)();
    }
    
    protected static function invokePrivateMethod(object $object, string $methodName, array $args = []): mixed
    {
        $reflector = new ReflectionClass($object);
        $method = $reflector->getMethod($methodName);
        
        $invoker = function () use ($method, $object, $args) {
            return $method->invoke($object, ...$args);
        };
        
        return Closure::bind($invoker, null, $object)();
    }
}
