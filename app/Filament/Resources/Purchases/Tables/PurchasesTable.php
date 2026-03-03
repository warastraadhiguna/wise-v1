<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Models\Purchase;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table; 
class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table    
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('purchase_date')->date()->sortable(),
                TextColumn::make('due_date')->date()->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'warning',
                        'posted' => 'success',
                        'void' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('payment_amount')->numeric()->label('Paid')->sortable(),
                TextColumn::make('details_count')->counts('details')->label('Items')->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                ->visible(fn (Purchase $record) => $record->status === 'draft'),                
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                //     ForceDeleteBulkAction::make(),
                //     RestoreBulkAction::make(),
                // ]),
            ])
            ->defaultSort('purchase_date', 'desc');
    }
}