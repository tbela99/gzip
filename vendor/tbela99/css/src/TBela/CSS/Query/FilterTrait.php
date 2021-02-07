<?php

namespace TBela\CSS\Query;

use Exception;
use InvalidArgumentException;

trait FilterTrait
{
    public function trim(array $value) {

        $j = count($value);

        while ($j--) {

            if (in_array($value[$j]->type, ['whitespace', 'separator'])) {

                array_splice($value, $j, 1);
            }
        }

        return $value;
    }
}