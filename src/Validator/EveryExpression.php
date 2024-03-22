<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class EveryExpression extends Constraint
{
    public $message = 'The value "{{ value }}" is not every valid expression.';
}