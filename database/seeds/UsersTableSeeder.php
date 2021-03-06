<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::create([
            'name' => 'Uzair Saeed',
            'email' => 'uzair@rusd.com',
            'country' => 'Douglas',
            'dob' => '1938-01-21',
            'is_admin' => true,
            'password' => Hash::make('secret'),
            'created_at' => now()
        ]);
        $admin->step()->sync(1);
        $role = Role::Where('name', 'admin')->first();
        $admin->assignRole([$role->id]);
        $admin->setStatus(CompleteProfile());

        $user = User::create([
            'name' => 'Murtuza Mehdi',
            'email' => 'murtuza@rusd.com',
            'country' => 'Douglas',
            'dob' => '1938-01-21',
            'is_admin' => false,
            'password' => Hash::make('secret'),
            'created_at' => now()
        ]);
        $user->step()->sync(1);
        $roleN = Role::where('name', 'normal')->first();
        $user->assignRole([$roleN->id]);
        $user->setStatus(CompleteProfile());

    }
}
