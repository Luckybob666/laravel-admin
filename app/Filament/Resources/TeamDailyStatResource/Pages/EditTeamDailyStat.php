<?php

namespace App\Filament\Resources\TeamDailyStatResource\Pages;

use App\Filament\Resources\TeamDailyStatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class EditTeamDailyStat extends EditRecord
{
    protected static string $resource = TeamDailyStatResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 编辑时不允许修改日期和团队，保持原值
        $data['date'] = $this->record->date;
        $data['team_id'] = $this->record->team_id;
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
                        ->body('删除团队每日数据将同时删除相关的所有数据，此操作不可恢复。')
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
        $teamName = $this->record->team->name ?? '未知团队';
        return Notification::make()
            ->success()
            ->title('团队每日数据更新成功')
            ->body($teamName . ' 在 ' . $this->record->date->format('Y-m-d') . ' 的数据已成功更新。');
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
