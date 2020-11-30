<?php
/**
 * DataTransferObject
 * Copyright 2020 Jamiel Sharief.
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

class DataTransferObject
{
    
   /**
     * @param array $properties an array of properties and data that you wish to set. No casting/conversion is done.
     */
    public function __construct(array $properties = [])
    {
        foreach ($properties as $key => $value) {
            $this->$key = $value;
        }

        if (method_exists($this, 'initialize')) {
            $this->initialize($properties);
        }
    }

    /**
    * Magic method for getting a property
    *
    * @param string $name
    * @return void
    */
    public function __get(string $name)
    {
        throw new RuntimeException(sprintf('Property %s does not exist', $name));
    }

    /**
     * Magic method for setting a property
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        throw new RuntimeException(sprintf('Property %s does not exist', $name));
    }

    /**
     * Magic method for classes exported by var_export
     *
     * @param array $data
     * @return static
     */
    public static function __set_state(array $data)
    {
        return new static($data);
    }

    /**
     * Converts this DataTransferObject to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass(static::class);

        $out = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() === false) {
                $out[$property->name] = $this->{$property->name};
            }
        }

        return $this->convertObjects($out);
    }

    /**
     * Alias for serializer
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->serialize();
    }

    /**
     * Creates an DTO instance from a string
     *
     * @param string $serialized
     * @return static
     */
    public static function fromString(string $serialized)
    {
        $dto = new static();
        $dto->deserialize($serialized);

        return $dto;
    }

    /**
     * Creates a DTO instance taking data from an Array and casting to the
     * correct types
     *
     * @param array $array
     * @return static
     */
    public static function fromArray(array $array)
    {
        $array = (new Marshaller(static::class))->marshall($array);

        return new static($array);
    }

    /**
     * Serializes this object, this can be overwritten
     *
     * @return string
     */
    public function serialize(): string
    {
        $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        return json_encode($this->toArray(), $jsonOptions);
    }

    /**
     * Handles the derializing of the string
     *
     * @param string $serialized
     * @return void
     */
    public function deserialize(string $serialized)
    {
        $data = json_decode($serialized, true);

        if (json_last_error()) {
            throw new RuntimeException('Error decoding JSON:  ' . json_last_error_msg());
        }

        $this->marshall($data);
    }

    /**
     * Marshalls an array of data
     *
     * @param array $data
     * @return void
     */
    protected function marshall(array $data): void
    {
        $data = (new Marshaller(static::class))->marshall($data);
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

    /**
     * Converts this DataTransferObject to a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->serialize();
    }

    /**
     * Helper function to deal with nested objects
     *
     * @param array $data
     * @return array
     */
    private function convertObjects(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->convertObjects($value);
            } elseif (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $value = $value->toArray();
                } else {
                    $value = method_exists($value, '__toString') ? (string) $value : (array) $value;
                }
            }
            $out[$key] = $value;
        }

        return $out;
    }
}
