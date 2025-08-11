<?php

namespace App\Filament\Resources\TeamDailyStatResource\Pages;

use App\Filament\Resources\TeamDailyStatResource;
use App\Models\TeamDailyStat;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateTeamDailyStat extends CreateRecord
{
    protected static string $resource = TeamDailyStatResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        
        if (isset($data['date']) && isset($data['team_id'])) {
            $date = $data['date'];
            $teamId = (int) $data['team_id'];
            
            // 检查该团队在该日期是否已存在数据
            if (TeamDailyStat::where('date', $date)->where('team_id', $teamId)->exists()) {
                $this->addError('date', '该团队在该日期的数据已存在，请选择其他日期或团队');
                
                Notification::make()
                    ->danger()
                    ->title('创建失败')
                    ->body('该团队在该日期的数据已存在，请选择其他日期或团队')
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
        $teamName = $this->record->team->name ?? '未知团队';
        return Notification::make()
            ->success()
            ->title('团队每日数据创建成功')
            ->body($teamName . ' 在 ' . $this->record->date->format('Y-m-d') . ' 的数据已成功创建。');
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
        if (isset($errors['team_id'])) {
            foreach ($errors['team_id'] as $error) {
                Notification::make()
                    ->danger()
                    ->title('团队错误')
                    ->body($error)
                    ->send();
            }
        }
    }
}
