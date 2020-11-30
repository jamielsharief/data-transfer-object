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
namespace DataTransferObject\Test\Fixture;

use DataTransferObject\DataTransferObject;

class Employee extends DataTransferObject
{
    public string $name;
    public string $email;
    public ?Employee $reportsTo;

    /**
     * @var \DataTransferObject\Test\Fixture\Employee[] $subordinates
     */
    public array $subordinates = [];

    public static string $foo = 'bar';
}
