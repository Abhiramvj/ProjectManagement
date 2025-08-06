<?php

namespace Tests\Unit\Actions\User;

use App\Actions\User\StoreUsers;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class EmployeeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'manager']);
    }

    #[Test]
    public function it_creates_a_user_and_attaches_them_to_a_team_via_the_pivot_table(): void
    {
        // 1. Arrange
        Storage::fake('public');
        $manager = User::factory()->create();
        $team = Team::factory()->create();
        $fakeImage = UploadedFile::fake()->image('avatar.jpg');

        $data = [
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
            'password' => 'password123',
            'work_mode' => 'remote',
            'parent_id' => $manager->id,
            'designation' => 'Software Engineer',
            'image' => $fakeImage,
            'role' => 'employee',
            'team_id' => $team->id, // The ID of the team to attach
        ];

        // 2. Act
        $action = new StoreUsers();
        $user = $action->handle($data);

        // 3. Assert
        $this->assertInstanceOf(User::class, $user);

        // Assert against the user's table (no team_id here)
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'john.smith@example.com',
            'parent_id' => $manager->id,
        ]);

        // Assert the image was stored correctly
        Storage::disk('public')->assertExists($user->image);

        // Assert the role was assigned
        $this->assertTrue($user->hasRole('employee'));

        // --- THE CORRECT PIVOT TABLE ASSERTION ---
        // Assert that a record exists in the 'team_user' pivot table
        // linking our new user to the correct team.
        $this->assertDatabaseHas('team_user', [
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);
    }

    #[Test]
    public function it_creates_a_user_without_attaching_a_team_if_team_id_is_not_provided(): void
    {
        // 1. Arrange: Minimal data set without a team_id.
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'password123',
            'role' => 'manager',
        ];

        // 2. Act
        $action = new StoreUsers();
        $user = $action->handle($data);

        // 3. Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertTrue($user->hasRole('manager'));

        // --- CORRECT PIVOT TABLE ASSERTION (MISSING) ---
        // Assert that NO record was created in the pivot table for this user.
        $this->assertDatabaseMissing('team_user', [
            'user_id' => $user->id,
        ]);
    }

    // In tests/Unit/Actions/User/StoreUsersTest.php

  #[Test]
    public function the_image_field_is_null_in_the_database_if_image_is_not_a_valid_file(): void
    {
        // 1. Arrange: Prepare two data sets with invalid image data.
        $dataWithNullImage = [
            'name' => 'No Image User',
            'email' => 'noimage@example.com',
            'password' => 'password123',
            'role' => 'employee',
            'image' => null, // Case 1: Explicitly null
        ];

        $dataWithStringImage = [
            'name' => 'Invalid Image User',
            'email' => 'invalid@example.com',
            'password' => 'password123',
            'role' => 'employee',
            'image' => 'not-a-file.jpg', // Case 2: A string, not an UploadedFile
        ];

        // 2. Act: Execute the action for each case.
        $action = new StoreUsers();
        $user1 = $action->handle($dataWithNullImage);
        $user2 = $action->handle($dataWithStringImage);

        // 3. Assert: Check that the 'image' column is null for both created users.
        $this->assertNull($user1->refresh()->image, "User image column should be null when image is null.");
        $this->assertNull($user2->refresh()->image, "User image column should be null when image is a string.");
    }

    // --- REVISED TEST 2: Check the file system ---
    #[Test]
    public function no_image_is_stored_to_the_disk_if_the_image_is_invalid(): void
    {
        Storage::fake('public');

        $data = [
            'name' => 'No File User',
            'email' => 'nofile@example.com',
            'password' => 'password123',
            'role' => 'employee',
            'image' => 'just-a-string.jpg',
        ];

        $action = new StoreUsers();
        $action->handle($data);
        $this->assertCount(0, Storage::disk('public')->files());
    }

#[Test]
public function it_throws_an_exception_when_assigning_a_non_existent_role(): void
{
    $data = [
        'name' => 'No Role User',
        'email' => 'norole@example.com',
        'password' => 'password123',
        'role' => 'non-existent-role',
    ];

    $this->expectException(RoleDoesNotExist::class);
    $action = new StoreUsers();
    $action->handle($data);
}

#[Test]
public function it_does_not_assign_a_role_if_the_role_key_is_empty_or_null(): void
{
    // Arrange
    $data = [
        'name' => 'Empty Role User',
        'email' => 'emptyrole@example.com',
        'password' => 'password123',
        'role' => '', // Empty string
    ];

    // Act
    $action = new StoreUsers();
    $user = $action->handle($data);

    // Assert
    // The user should have been created successfully.
    $this->assertNotNull($user->id);
    // The user should have no roles assigned.
    $this->assertCount(0, $user->getRoleNames());
}
}
