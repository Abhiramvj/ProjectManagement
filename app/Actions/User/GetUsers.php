<?php

namespace App\Actions\User;

use App\Models\User;
use App\Actions\User\CreateUsers; // ✅ Import the CreateUsers action

class GetUsers
{
	protected $createUsers;

	public function __construct(CreateUsers $createUsers)
	{
		$this->createUsers = $createUsers;
	}

	public function handle()
	{
		// Reuse additional data from CreateUsers
		$createUsersData = $this->createUsers->handle();

		return array_merge([
			'users' => User::with('roles:id,name')->latest()->paginate(10),
		], $createUsersData);
	}
}
