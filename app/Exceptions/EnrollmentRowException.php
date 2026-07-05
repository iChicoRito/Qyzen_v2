<?php

namespace App\Exceptions;

use RuntimeException;

// Task 39: thrown by EnrollmentsImport at the first invalid row so the controller can
// halt and flash an error naming the file + the actual spreadsheet row number.
class EnrollmentRowException extends RuntimeException
{
    public function __construct(public int $row)
    {
        parent::__construct("Enrollment import row {$row} is invalid.");
    }
}
