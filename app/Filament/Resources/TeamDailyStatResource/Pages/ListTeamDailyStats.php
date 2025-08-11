<?php

namespace App\Filament\Resources\TeamDailyStatResource\Pages;

use App\Filament\Resources\TeamDailyStatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeamDailyStats extends ListRecords
{
    protected static string $resource = TeamDailyStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
