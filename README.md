# Data Transfer Object (DTO)

![license](https://img.shields.io/badge/license-MIT-brightGreen.svg)
[![build](https://github.com/jamielsharief/data-transfer-object/workflows/CI/badge.svg)](https://github.com/jamielsharief/data-transfer-object/actions)
[![coverage status](https://coveralls.io/repos/github/jamielsharief/data-transfer-object/badge.svg?branch=main)](https://coveralls.io/github/jamielsharief/data-transfer-object?branch=main)


## Create

To create a `DataTransferObject` create the class and use public properties.

```php
use DataTransferObject\DataTransferObject;

class Employee extends DataTransferObject
{
    public string $name;
    public string $email;
    public ?Employee $reportsTo;
    public int $age;
    public bool $active = true;

    /**
     * @var \App\DataTransferObject\Employee[] $subordinates
     */
    public array $subordinates = [];
}
```

Then to set the data

```php
$employee = new Employee();
$employee->name = 'sarah';
```

You can also mass set the properties using an array when constructing the argument. Note this only sets, does not convert or extract, see `fromArray`

```php

 $sarah = new Employee([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'reportsTo' => $claire
        ]);
```

### Build 

When you use the `DataTransferObject` constructor, it simply sets the values that you are providing, however when you need to convert data and cast types, you can use the `fromArray` method.

The `fromArray` extracts relevant data and it:

1. casts built in types bool, int, string, float etc
2. will create a `DataTransferObject`, `DateTime` or any other object that has a `__set_state` magic method.

It will only extract fields defined in the `DataTransferObject` and will only throw an error, if after extracting data, a property on the `DataTransferObject` was not initialized.

For example, related `DataTransferObjects` such as `belongsTo` and `hasMany` will be marshalled. For `hasMany` to work create an array and set the DocBlock definition like below:

```php
class Employee extends DataTransferObject
{
    public string $name;
    public string $email;
    public ?Employee $reportsTo;

    /**
     * @var \App\DataTransferObjects\Employee[] $subordinates
     */
    public array $subordinates = [];
}
```

To use the `fromArray` method

```php
 $employee = Employee::fromArray([
    'name' => 'Sarah',
    'email' => 'sarah@example.com',
    'reportsTo' => [
        'name' => 'Claire',
        'email' => 'claire@example.com',
    ],
    'subordinates' => [
        [
            'name' => 'Jon',
            'email' => 'jon@example.com'
        ]
    ]
]);
```

### Converting to an Array

To convert the `DataTransferObject` and any **nested** objects to an array if the object has an `toArray` method or does not have a `__toString` method.

```php
$employee->toArray();
```

### Serialization / Deserialization

The default serialization method is JSON, for a different method, e.g. XML you can override the `serialize` and `deserialize` methods. Only public properties will be used in the serialization and therefore deserialization process.

### To string

To convert a `DataTransferObject` to a string.

```php
$employee->toString();
```

### From string

To convert a string to a `DataTransferObject`

```php
$employee = Contact::fromString(
    '{"name":"Jon","company":"Snow Enterprises","email":"jon@example.com","age":33,"unsubscribed":false}'
);
```

### Initialize Hook

When the `DataTransferObject` is constructed it will call `initialize` method if it is available, this is a hook incase
you need to override the constructor.

### Exception Handling

If you try to set or get a property that does not exist, a `RuntimeException` will be thrown.

## Resources

- [Data Transfer Object (DTO) - Martin Fowler](https://martinfowler.com/eaaCatalog/dataTransferObject.html)
- [Data Transfer Object - Wikipedia](https://en.wikipedia.org/wiki/Data_transfer_object)