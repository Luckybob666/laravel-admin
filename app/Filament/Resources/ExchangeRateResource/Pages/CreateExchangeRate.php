<?php

namespace App\Filament\Resources\ExchangeRateResource\Pages;

use App\Filament\Resources\ExchangeRateResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateExchangeRate extends CreateRecord
{
    protected static string $resource = ExchangeRateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('汇率创建成功')
            ->body($this->record->rate_date->format('Y-m-d') . ' 的汇率已成功创建。');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        // 自定义验证错误处理
        $errors = $exception->errors();
        if (isset($errors['rate_date'])) {
            foreach ($errors['rate_date'] as $error) {
                Notification::make()
                    ->danger()
                    ->title('日期错误')
                    ->body($error)
                    ->send();
            }
        }
    }
}
