<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1) 确保角色存在（注意 guard_name 要与面板登录守卫一致，通常是 web）
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        // 2) 创建或更新管理员账户
        $user = User::updateOrCreate(
            ['username' => 'admin'], // 作为查找条件也会写入
            [
                'name' => 'Super Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('123456'), // 自行修改
            ]
        );

        // 3) 赋予角色（避免重复赋值）
        if (! $user->hasRole($role->name)) {
            $user->assignRole($role);
        }
    }
}
