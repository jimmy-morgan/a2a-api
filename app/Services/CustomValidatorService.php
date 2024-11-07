<?php

namespace App\Services;


class CustomValidatorService
{

    public function validateMoney($attribute, $value, $parameters, $validator)
    {
        if (preg_match('/^[+-]?[0-9]{1,3}(?:,?[0-9]{3})*(?:\.[0-9]{2})?$/', $value)) {
            return true;
        }
        return false;
    }

    public function validatePhone($attribute, $value, $parameters, $validator)
    {
        if (preg_match('/[0-9]{10}/', $value)) {
            return true;
        }
        return false;
    }
}
