<?php
namespace App\Http\Middleware;

class VerifyCsrfToken extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken
{
    protected function runningUnitTests()
    {
        return !config('security.enforce_csrf', false) && parent::runningUnitTests();
    }
}