<?php

namespace App\Filament\Resources\ExchangeRateResource\Pages;

use App\Filament\Resources\ExchangeRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class EditExchangeRate extends EditRecord
{
    protected static string $resource = ExchangeRateResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 编辑时不允许修改日期，保持原日期
        $data['rate_date'] = $this->record->rate_date;
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    Notification::make()
                        ->warning()
                        ->title('确认删除')
                        ->body('删除汇率记录将同时删除相关的所有数据，此操作不可恢复。')
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('汇率更新成功')
            ->body($this->record->rate_date->format('Y-m-d') . ' 的汇率信息已成功更新。');
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
