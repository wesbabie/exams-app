<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Development only: one staff account per role, all with the password "password".
 * Do not run this on a real server.
 *
 * Run with: php artisan db:seed --class=DemoStaffSeeder
 */
class DemoStaffSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password');

        $accounts = [
            ['Super Admin', 'admin@exams.test', 'super_admin'],
            ['Exam Officer', 'officer@exams.test', 'exam_officer'],
            ['Question Author', 'author@exams.test', 'question_author'],
            ['Reviewer', 'reviewer@exams.test', 'reviewer'],
            ['Invigilator', 'invigilator@exams.test', 'invigilator'],
            ['Committee Member', 'committee@exams.test', 'committee'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $name, 'password' => $password, 'role' => $role, 'is_active' => true,
                    'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                ]
            );
        }

        $this->command->info('Staff demo accounts ready. Password for all: password');
        $this->command->table(['Email', 'Role'], array_map(fn ($a) => [$a[1], $a[2]], $accounts));
    }
}
