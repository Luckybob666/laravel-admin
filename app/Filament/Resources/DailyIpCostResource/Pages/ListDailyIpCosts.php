<?php

namespace App\Filament\Resources\DailyIpCostResource\Pages;

use App\Filament\Resources\DailyIpCostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyIpCosts extends ListRecords
{
    protected static string $resource = DailyIpCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
