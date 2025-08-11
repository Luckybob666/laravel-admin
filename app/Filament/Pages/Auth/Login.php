<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\TextInput;

class Login extends BaseLogin
{
    // 只用用户名登录（想“用户名或邮箱”也可以用之前给的方案B）
    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('username')
            ->label('用户名')
            ->required()
            ->autofocus();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }
}
