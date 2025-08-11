<?php

namespace App\Filament\Resources\DailyIpCostResource\Pages;

use App\Filament\Resources\DailyIpCostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class EditDailyIpCost extends EditRecord
{
    protected static string $resource = DailyIpCostResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 编辑时不允许修改日期，保持原日期
        $data['date'] = $this->record->date;
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
                        ->body('删除IP费用记录将同时删除相关的所有数据，此操作不可恢复。')
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
            ->title('IP费用更新成功')
            ->body($this->record->date->format('Y-m-d') . ' 的IP费用信息已成功更新。');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        // 自定义验证错误处理
        $errors = $exception->errors();
        if (isset($errors['date'])) {
            foreach ($errors['date'] as $error) {
                Notification::make()
                    ->danger()
                    ->title('日期错误')
                    ->body($error)
                    ->send();
            }
        }
    }
}
