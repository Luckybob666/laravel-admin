<?php

namespace App\Filament\Resources\FixedExpenseResource\Pages;

use App\Filament\Resources\FixedExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class EditFixedExpense extends EditRecord
{
    protected static string $resource = FixedExpenseResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 编辑时不允许修改月份，保持原月份
        $data['month_date'] = $this->record->month_date;
        
        // 确保月份格式正确（设置为该月第一天）
        if (isset($data['month_date'])) {
            $data['month_date'] = \Carbon\Carbon::parse($data['month_date'])->startOfMonth()->toDateString();
        }
        
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
                        ->body('删除固定费用记录将同时删除相关的所有数据，此操作不可恢复。')
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
            ->title('固定费用更新成功')
            ->body($this->record->month_date->format('Y-m') . ' 的固定费用信息已成功更新。');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        // 自定义验证错误处理
        $errors = $exception->errors();
        if (isset($errors['month_date'])) {
            foreach ($errors['month_date'] as $error) {
                Notification::make()
                    ->danger()
                    ->title('月份错误')
                    ->body($error)
                    ->send();
            }
        }
    }
}
