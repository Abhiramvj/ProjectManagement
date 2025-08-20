<?php

namespace App\Actions\User;

use App\Models\User;

class EditUser
{
    /**
     * The action to get data for the creation form.
     * We reuse it here to get all the dropdown options.
     *
     * @var \App\Actions\User\CreateUsers
     */
    protected $createUsers;

    /**
     * Inject the CreateUsers action.
     */
    public function __construct(CreateUsers $createUsers)
    {
        $this->createUsers = $createUsers;
    }

    /**
     * Prepare the props for the user edit page.
     *
     * @param  \App\Models\User  $user  The user being edited.
     */
    public function handle(User $user): array
    {

        $formProps = $this->createUsers->handle();

        $user->load('roles');

        return array_merge(
            ['user' => $user],
            $formProps
        );
    }
}
