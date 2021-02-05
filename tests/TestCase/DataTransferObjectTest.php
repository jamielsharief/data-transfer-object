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
namespace DataTransferObject\Test\TestCase;

use DateTime;
use stdClass;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use DataTransferObject\Test\Fixture\User;
use DataTransferObject\Test\Fixture\Types;
use DataTransferObject\Test\Fixture\Contact;
use DataTransferObject\Test\Fixture\Employee;

class DataTransferObjectTest extends TestCase
{
    public function testConstruct()
    {
        $contact = new Contact([
            'name' => 'Jon',
            'company' => 'Snow Enterprises',
            'email' => 'jon@example.com',
            'age' => 33
        ]);

        $this->assertEquals('Jon', $contact->name);
    }

    public function testToArray()
    {
        $data = [
            'name' => 'Jon',
            'company' => 'Snow Enterprises',
            'email' => 'jon@example.com',
            'age' => 33,
            'unsubscribed' => true
        ];

        $contact = new Contact($data);

        $this->assertEquals($data, $contact->toArray());
    }

    public function testToArrayNestedObject()
    {
        $claire = new Employee([
            'name' => 'Claire',
            'email' => 'claire@example.com',
            'reportsTo' => null
        ]);

        $sarah = new Employee([
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'reportsTo' => $claire
        ]);

        $this->assertEquals('Claire', $sarah->toArray()['reportsTo']['name']);
    }

    /**
     * Check nested array with key
     * @return void
     */
    public function testToArrayNestedArray()
    {
        $claire = new Employee([
            'name' => 'Claire',
            'email' => 'claire@example.com',
            'reportsTo' => null,
            'subordinates' => [
                1 => new Employee([
                    'name' => 'Sarah',
                    'email' => 'sarah@example.com',
                    'reportsTo' => null
                ])
            ]
        ]);

        $this->assertEquals('Sarah', $claire->toArray()['subordinates'][1]['name']);
    }

    public function testToJson()
    {
        $contact = new Contact([
            'name' => 'Jon',
            'company' => 'Snow Enterprises',
            'email' => 'jon@example.com',
            'age' => 33
        ]);
        $this->assertEquals('{"name":"Jon","company":"Snow Enterprises","email":"jon@example.com","age":33,"unsubscribed":false}', $contact->toString());
    }

    public function testToString()
    {
        $contact = Contact::fromArray([
            'name' => 'Jon',
            'company' => 'Snow Enterprises',
            'email' => 'jon@example.com',
            'age' => 33
        ]);
        $this->assertEquals('{"name":"Jon","company":"Snow Enterprises","email":"jon@example.com","age":33,"unsubscribed":false}', (string) $contact);
    }

    public function testSet()
    {
        $contact = new Contact([
            'name' => 'Jon',
            'company' => 'Snow Enterprises',
            'email' => 'jon@example.com',
            'age' => 33
        ]);
        $this->expectException(RuntimeException::class);
        $contact->foo = 'foo';
    }

    /**
     * Here want to reach hasProperty in Marshaller
     */
    public function testSetEmptyArray()
    {
        $this->expectException(RuntimeException::class);
        new Employee([
            'name' => 'foo',
            'email' => 'foo@example.com',
            'foo' => ['foo']
        ]);
    }

    public function testGet()
    {
        $contact = new Contact([
            'name' => 'Jon',
            'company' => 'Snow Enterprises',
            'email' => 'jon@example.com',
            'age' => 33
        ]);
        $this->expectException(RuntimeException::class);
        $contact->foo;
    }

    public function testSetState()
    {
        $contact = Contact::__set_state([
            'name' => 'Jon',
            'company' => 'Snow Enterprises',
            'email' => 'jon@example.com',
            'age' => 33
        ]);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertEquals('jon@example.com', $contact->email);
    }

    public function testCreateNestedDataTransferObject()
    {
        $data = [
            'name' => 'Sarah',
            'email' => 'sarah@example.com',
            'reportsTo' => [
                'name' => 'Claire',
                'email' => 'claire@example.com',
                'reportsTo' => null
            ],
            'subordinates' => [
                [
                    'name' => 'Jon',
                    'email' => 'jon@example.com',
                    'reportsTo' => null
                ]
            ]
        ];

        $employee = Employee::fromArray($data);

        $this->assertInstanceOf(Employee::class, $employee->reportsTo);
        $this->assertEquals('Claire', $employee->reportsTo->name);
        $this->assertInstanceOf(Employee::class, $employee->subordinates[0]);
        $this->assertEquals('Jon', $employee->subordinates[0]->name);
    }

    public function testAgainstDocBlockDefinitionException()
    {
        $this->expectException(RuntimeException::class);
        
        Employee::fromArray([
            'name' => 'Claire',
            'email' => 'claire@example.com',
            'reportsTo' => null,
            'subordinates' => [
                new stdClass()
            ]
        ]);
    }

    public function testCasting()
    {
        $types = Types::fromArray([
            'string' => 123,
            'bool' => 1,
            'integer' => '123',
            'float' => '123.45',
            'array' => 'foo',
            'null' => '',
            'dt' => (array) new DateTime('2020-01-01 14:00:00')
        ]);
        $this->assertEquals('123', $types->string);
        $this->assertEquals(true, $types->bool);
        $this->assertEquals(123, $types->integer);
        $this->assertEquals(123.45, $types->float);
        $this->assertEquals(['foo'], $types->array);
        $this->assertEquals(null, $types->null);
        $this->assertInstanceOf(DateTime::class, $types->dt);
        $this->assertEquals('Wednesday, 01-Jan-2020 14:00:00 UTC', $types->dt->format(DateTime::COOKIE));

        // test casting of DT
        $json = $types->toString();
        $this->assertEquals($types, Types::fromString($json));
    }

    /**
     * This is an example of an object that is not supported
     */
    public function testToArrayUnkown()
    {
        $user = new User();
        $user->name = 'foo';
        $user->timestamp = new DateTime();

        $array = $user->toArray();
        $this->assertIsArray($array['timestamp']);
    }

    public function testUnintializedProperty()
    {
        $this->expectException(RuntimeException::class);
        Contact::fromArray([
            'company' => 'Snow Enterprises'
        ]);
    }

    public function testFromJson()
    {
        $json = '{"name":"Jon","company":"Snow Enterprises","email":"jon@example.com","age":33,"unsubscribed":false}';
        $contact = Contact::fromString($json);

        $this->assertInstanceOf(Contact::class, $contact);
    }

    public function testParseError()
    {
        $this->expectException(RuntimeException::class);
        Contact::fromString('<-o->');
    }
}
