<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('logs in an admin and returns the correct role id', function () {
    $role = Role::create([
        'name' => 'Admin',
        'slug' => 'admin',
    ]);

    User::create([
        'name' => 'Test Admin',
        'email' => 'testadmin@example.com',
        'password' => Hash::make('secret'),
        'role_id' => $role->id,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'testadmin@example.com',
        'password' => 'secret',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('user.role_id', $role->id);
});