<?php

namespace App\Http\Helpers;

use App\Models\Preference;

// generates the employeement number for Company Admin
class GenerateEmployeeNumber
{
    public static function generateEmployeeNumber(): string
    {
        try {
            $latestEmployeeNumberPref = Preference::where('code', 'EMP')->first();

            if ($latestEmployeeNumberPref) {
                $latestEmployeeNumber = (int)$latestEmployeeNumberPref->value;
                $nextEmployeeNumber = 'EMP' . str_pad($latestEmployeeNumber + 1, 5, '0', STR_PAD_LEFT);
                $latestEmployeeNumberPref->value = $latestEmployeeNumber + 1;
                $latestEmployeeNumberPref->save();
            } else {
                $nextEmployeeNumber = 'EMP00001';
                $latestEmployeeNumberPref = new Preference();
                $latestEmployeeNumberPref->code = 'EMP';
                $latestEmployeeNumberPref->value = 1;
                $latestEmployeeNumberPref->save();
            }

            return $nextEmployeeNumber;
        } catch (\Exception $e) {
            return error('An unexpected error occurred.', [], $e);
        }
    }
}
