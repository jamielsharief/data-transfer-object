<?php
/**
 * DataTransferObject
 * Copyright 2020-2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace DataTransferObject;

use ReflectionClass;
use RuntimeException;
use ReflectionProperty;
use ReflectionNamedType;

class Marshaller
{
    const DOC_DEFINITION = '/\@var \\\([a-zA-z09_\\\]+)\[] \$/';

    private ReflectionClass $reflection;
    private string $class;

    /**
     * @param \Reflectionclass $reflection
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->reflection = new ReflectionClass($class);
    }

    /**
     * Creates a DataTransferObject from an array of data, values are cast to PHP types and DataTransferObject, only
     * properties of DataTransferObject are used from the array. This can be used to take data from a form or result
     * set from a database, and quickly create the DataTransferObject.
     *
     * @param string $DataTransferObjectClass
     * @param array $data
     * @return array
     */
    public function marshall(array $data): array
    {
        $dto = new $this->class;

        $out = [];

        foreach ($this->reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $key = $property->getName();

            if ($property->isStatic()) {
                continue;
            }
            $propertyType = $property->getType();

            if (! array_key_exists($key, $data)) {
                if ($property->isInitialized($dto) === false) {
                    throw new RuntimeException(sprintf("Property '%s' was not initialized", $key));
                }
                continue;
            }

            $value = $data[$key];

            // cast types
            if ($propertyType->isBuiltin() && $propertyType instanceof ReflectionNamedType) {
                $value = $this->castType($value, $propertyType->getName(), $propertyType->allowsNull());
            }
          
            if ($value && is_array($value)) {
                $value = $this->fromArray($value, $key);
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     *  Marshalls an array to either a DataTransferObject or an array of DataTransferObject
     *
     * @param array $value
     * @param string $property
     * @return mixed
     */
    private function fromArray(array $value, string $property)
    {
        $reflectionProperty = $this->reflection->getProperty($property);

        // extract class name from a docblock defintion e.g @var \App\DataTransferObjects\Employee[] $employees
        $comment = $reflectionProperty ->getDocComment();
        if ($comment && preg_match(self::DOC_DEFINITION, $comment, $matches)) {
            return $this->many($value, $matches[1]);
        }

        $type = $reflectionProperty->getType();
        if ($type instanceof ReflectionNamedType && $type->isBuiltin() === false) {
            $className = $type->getName();
            if ($this->hasSetStateMethod($className)) {
                $value = $className::__set_state($value);
            }
        }

        return $value;
    }

    /**
     * Casts a value to a specifc type
     *
     * @see https://www.php.net/manual/en/language.types.type-juggling.php#language.types.typecasting
     *
     * @param mixed $value
     * @param string $type
     * @param boolean $allowsNull
     * @return mixed
     */
    private function castType($value, string $type, bool $allowsNull)
    {
        if ($allowsNull && ($value === null || $value === '')) {
            return null;
        }
      
        switch ($type) {
            case 'int':
                $value = (int) $value;
            break;
            case 'bool':
                $value = (bool) $value;
            break;
            case 'float':
                $value = (float) $value;
            break;
            case 'string':
                $value = (string) $value;
            break;
            case 'array':
                $value = (array) $value;
            break;
        }

        return $value;
    }

    /**
     * Builds an array of objects based upon the parsed Doc block defintion, which is only for
     *
     * 1. If each row of values is an array and it is a sub class of DataTransferObject, then it converts it
     * 2. Else each row should be an object
     *
     * @param array $values
     * @param string $className
     * @return array
     */
    private function many(array $values, string $className): array
    {
        $hasSetState = $this->hasSetStateMethod($className);

        $out = [];
        foreach ($values as $value) {
            if (is_array($value) && $hasSetState) {
                $value = $className::__set_state($value);
            } elseif (! $value instanceof $className) {
                throw new RuntimeException('Invalid object in array');
            }
            
            $out[] = $value;
        }

        return $out;
    }

    /**
     * @param string $className
     * @return boolean
     */
    private function hasSetStateMethod(string $className): bool
    {
        return (new ReflectionClass($className))->hasMethod('__set_state');
    }
}
