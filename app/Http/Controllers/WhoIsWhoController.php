<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\View\View;

class WhoIsWhoController
{
    /**
     * The "who is who" page; gone (404) when switched off in the settings or when nobody is listed.
     */
    public function __invoke(): View
    {
        abort_unless(Employee::pageIsAvailable(), 404);

        return view('who-is-who', [
            'employees' => Employee::published()->ordered()->get(),
        ]);
    }
}
