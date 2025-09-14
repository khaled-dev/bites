<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_users(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'dob' => '1990-01-01',
        ]);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'dob',
                        'dob_formated',
                    ]
                ]
            ])
            ->assertJsonPath('data.0.name', $user->name)
            ->assertJsonPath('data.0.dob', $user->dob->toDateString())
            ->assertJsonPath('data.0.dob_formated', $user->dob->format('d M, Y'));
    }

    public function test_can_filter_users_by_name(): void
    {
        $johnDoe = User::factory()->create(['name' => 'John Doe']);
        $janeDoe = User::factory()->create(['name' => 'Jane Doe']);
        $bobSmith = User::factory()->create(['name' => 'Bob Smith']);

        $response = $this->getJson('/api/v1/users?name=Doe');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', $johnDoe->name)
            ->assertJsonPath('data.1.name', $janeDoe->name);

        // Assert that Bob Smith is not in the response
        $responseData = $response->json('data');
        $this->assertNotContains($bobSmith->name, collect($responseData)->pluck('name')->all());
    }

    public function test_can_filter_users_by_dob(): void
    {
        $user1 = User::factory()->create(['dob' => '1990-01-01']);
        $user2 = User::factory()->create(['dob' => '1995-01-01']);

        $response = $this->getJson('/api/v1/users?dob=1990-01-01');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.dob', '1990-01-01');

        // Assert that user with different DOB is not in the response
        $responseData = $response->json('data');
        $this->assertNotContains('1995-01-01', collect($responseData)->pluck('dob')->all());
    }

    public function test_can_filter_users_by_name_and_dob(): void
    {
        $matchingUser = User::factory()->create([
            'name' => 'John Doe',
            'dob' => '1990-01-01'
        ]);

        $sameName = User::factory()->create([
            'name' => 'John Doe',
            'dob' => '1995-01-01'
        ]);

        $sameDob = User::factory()->create([
            'name' => 'Jane Doe',
            'dob' => '1990-01-01'
        ]);

        $response = $this->getJson('/api/v1/users?name=John&dob=1990-01-01');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $matchingUser->name)
            ->assertJsonPath('data.0.dob', $matchingUser->dob->toDateString());

        // Assert that users with partial matches are not in the response
        $responseData = $response->json('data');

        $this->assertNotContains($sameName->name . ':' . $sameName->dob->toDateString(),
            collect($responseData)->map(fn($item) => $item['name'] . ':' . $item['dob'])->all(),
            'User with same name but different DOB should not be in response'
        );

        $this->assertNotContains($sameDob->name . ':' . $sameDob->dob->toDateString(),
            collect($responseData)->map(fn($item) => $item['name'] . ':' . $item['dob'])->all(),
            'User with same DOB but different name should not be in response'
        );
    }
}
