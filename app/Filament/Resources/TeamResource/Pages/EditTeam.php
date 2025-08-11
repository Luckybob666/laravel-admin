<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use App\Models\Team;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
                        ->body('删除团队将同时删除相关的所有数据，此操作不可恢复。')
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
            ->title('团队更新成功')
            ->body('团队 "' . $this->record->name . '" 的信息已成功更新。');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        // 自定义验证错误处理
        $errors = $exception->errors();
        if (isset($errors['name'])) {
            foreach ($errors['name'] as $error) {
                Notification::make()
                    ->danger()
                    ->title('团队名称错误')
                    ->body($error)
                    ->send();
            }
        }
    }
}
