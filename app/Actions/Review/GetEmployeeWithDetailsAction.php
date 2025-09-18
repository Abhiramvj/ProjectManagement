<?php

namespace App\Actions\Review;

use App\Models\User;

class GetEmployeeWithDetailsAction
{
    public function execute(int $employeeId): User
    {
        return User::select('id', 'name', 'email', 'designation as role', 'hire_date')
            ->findOrFail($employeeId);
    }
}
