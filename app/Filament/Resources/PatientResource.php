<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Filament\Resources\PatientResource\RelationManagers;
use App\Models\Patient;
use Carbon\Carbon;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Grid as LayoutGrid;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Stack;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'cat' => 'Cat',
                        'dog' => 'Dog',
                        'rabbit' => 'Rabbit',
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('date_of_birth')
                    ->required()
                    ->maxDate(now()),
                Forms\Components\Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone number')
                            ->tel()
                            ->required(),
                    ])
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->columns([
                Stack::make([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable(),
                    // Tables\Columns\CheckboxColumn::make('is_pregnant'),
                    Tables\Columns\SelectColumn::make('is_pregnant')
                        ->options([
                            true => 'YES',
                            false => 'NO',
                        ]),
                    Tables\Columns\TextColumn::make('type'),
                    Tables\Columns\TextColumn::make('date_of_birth'),
                    Tables\Columns\TextColumn::make('owner.name')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->toggleable(isToggledHiddenByDefault: true)
                ])
            ])->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                TernaryFilter::make('is_pregnant'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('dibuat dari'),
                        DatePicker::make('dibuat sampai'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['dibuat dari'] && !$data['dibuat sampai']) {
                            return null;
                        }
                        $indicatorFrom = 'dibuat dari ' . Carbon::parse($data['dibuat dari'])->toFormattedDateString();
                        $indicatorUntil = ' to ' . Carbon::parse($data['dibuat sampai'])->toFormattedDateString();
                        return $indicatorFrom . " " . $indicatorUntil;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dibuat dari'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['dibuat sampai'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),


            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TreatmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}
