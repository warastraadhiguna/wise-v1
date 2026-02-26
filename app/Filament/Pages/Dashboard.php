<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStats;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';
    protected static ?int $navigationSort = -1; 
    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStats::class, // ✅ Tambahkan widget statistik
        ];
    }

}