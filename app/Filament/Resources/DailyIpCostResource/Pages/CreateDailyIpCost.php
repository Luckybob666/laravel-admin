<?php

namespace App\Filament\Resources\DailyIpCostResource\Pages;

use App\Filament\Resources\DailyIpCostResource;
use App\Models\DailyIpCost;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateDailyIpCost extends CreateRecord
{
    protected static string $resource = DailyIpCostResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        
        if (isset($data['date'])) {
            $date = $data['date'];
            
            // 检查日期是否已存在
            if (DailyIpCost::where('date', $date)->exists()) {
                $this->addError('date', '该日期的IP费用记录已存在，请选择其他日期');
                
                Notification::make()
                    ->danger()
                    ->title('创建失败')
                    ->body('该日期的IP费用记录已存在，请选择其他日期')
                    ->persistent()
                    ->send();
                
                $this->halt();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('IP费用创建成功')
            ->body($this->record->date->format('Y-m-d') . ' 的IP费用已成功创建。');
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
