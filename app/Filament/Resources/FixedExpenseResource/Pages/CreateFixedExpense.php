<?php

namespace App\Filament\Resources\FixedExpenseResource\Pages;

use App\Filament\Resources\FixedExpenseResource;
use App\Models\FixedExpense;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class CreateFixedExpense extends CreateRecord
{
    protected static string $resource = FixedExpenseResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        
        if (isset($data['month_date'])) {
            $monthDate = Carbon::parse($data['month_date'])->startOfMonth()->toDateString();
            
            // 检查月份是否已存在
            if (FixedExpense::where('month_date', $monthDate)->exists()) {
                $this->addError('month_date', '该月份已存在固定费用记录，请选择其他月份');
                
                Notification::make()
                    ->danger()
                    ->title('创建失败')
                    ->body('该月份已存在固定费用记录，请选择其他月份')
                    ->persistent()
                    ->send();
                
                $this->halt();
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 确保月份格式正确（设置为该月第一天）
        if (isset($data['month_date'])) {
            $data['month_date'] = Carbon::parse($data['month_date'])->startOfMonth()->toDateString();
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('固定费用创建成功')
            ->body($this->record->month_date->format('Y-m') . ' 的固定费用已成功创建。');
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
