<?php

namespace App\Filament\Pages;

use App\Models\SecretPriceSetting;
use App\Support\SecretPriceCode;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use UnitEnum;

class SecretPriceCodePage extends Page
{
    protected string $view = 'filament.pages.secret-price-code-page';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Kode Rahasia Harga';

    protected static string | UnitEnum | null $navigationGroup = 'Data Umum';

    protected static ?string $slug = 'settings/secret-price-code';

    protected static ?int $navigationSort = 80;

    public array $digits = [];

    public array $repeats = [];

    public string $calculatorActual = '';

    public string $calculatorSecret = '';

    public string $calculatorDecoded = '';

    public function mount(): void
    {
        $setting = SecretPriceSetting::singleton();

        $this->digits = $setting->digitMap();
        $this->repeats = $setting->repeatMap();
    }

    public function save(): void
    {
        $data = [
            'digits' => $this->digits,
            'repeats' => $this->repeats,
        ];

        Validator::make($data, [
            'digits.0' => ['required', 'string', 'max:20'],
            'digits.1' => ['required', 'string', 'max:20'],
            'digits.2' => ['required', 'string', 'max:20'],
            'digits.3' => ['required', 'string', 'max:20'],
            'digits.4' => ['required', 'string', 'max:20'],
            'digits.5' => ['required', 'string', 'max:20'],
            'digits.6' => ['required', 'string', 'max:20'],
            'digits.7' => ['required', 'string', 'max:20'],
            'digits.8' => ['required', 'string', 'max:20'],
            'digits.9' => ['required', 'string', 'max:20'],
            'repeats.2' => ['required', 'string', 'max:20'],
            'repeats.3' => ['required', 'string', 'max:20'],
            'repeats.4' => ['required', 'string', 'max:20'],
            'repeats.5' => ['required', 'string', 'max:20'],
            'repeats.6' => ['required', 'string', 'max:20'],
            'repeats.7' => ['required', 'string', 'max:20'],
            'repeats.8' => ['required', 'string', 'max:20'],
        ])->after(function ($validator) {
            $tokens = collect(array_merge(array_values($this->digits), array_values($this->repeats)))
                ->map(fn ($value) => strtoupper(trim((string) $value)))
                ->filter()
                ->values();

            if ($tokens->count() !== $tokens->unique()->count()) {
                $validator->errors()->add('digits', 'Semua kode digit dan pengali harus unik supaya bisa didecode.');
            }
        })->validate();

        $setting = SecretPriceSetting::singleton();
        $setting->fill([
            'digit_0' => strtoupper(trim((string) $this->digits['0'])),
            'digit_1' => strtoupper(trim((string) $this->digits['1'])),
            'digit_2' => strtoupper(trim((string) $this->digits['2'])),
            'digit_3' => strtoupper(trim((string) $this->digits['3'])),
            'digit_4' => strtoupper(trim((string) $this->digits['4'])),
            'digit_5' => strtoupper(trim((string) $this->digits['5'])),
            'digit_6' => strtoupper(trim((string) $this->digits['6'])),
            'digit_7' => strtoupper(trim((string) $this->digits['7'])),
            'digit_8' => strtoupper(trim((string) $this->digits['8'])),
            'digit_9' => strtoupper(trim((string) $this->digits['9'])),
            'repeat_2' => strtoupper(trim((string) $this->repeats['2'])),
            'repeat_3' => strtoupper(trim((string) $this->repeats['3'])),
            'repeat_4' => strtoupper(trim((string) $this->repeats['4'])),
            'repeat_5' => strtoupper(trim((string) $this->repeats['5'])),
            'repeat_6' => strtoupper(trim((string) $this->repeats['6'])),
            'repeat_7' => strtoupper(trim((string) $this->repeats['7'])),
            'repeat_8' => strtoupper(trim((string) $this->repeats['8'])),
        ])->save();

        $this->digits = $setting->digitMap();
        $this->repeats = $setting->repeatMap();

        Notification::make()
            ->title('Kode rahasia berhasil disimpan.')
            ->success()
            ->send();
    }

    public function calculateFromActual(): void
    {
        $this->calculatorSecret = SecretPriceCode::encode($this->calculatorActual);
        $this->calculatorDecoded = preg_replace('/\D+/', '', (string) $this->calculatorActual) ?: '0';
    }

    public function calculateFromSecret(): void
    {
        try {
            $decoded = SecretPriceCode::decode(strtoupper($this->calculatorSecret));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'calculatorSecret' => $exception->getMessage(),
            ]);
        }

        $this->calculatorDecoded = $decoded;
        $this->calculatorActual = number_format((float) $decoded, 0, ',', '.');
        $this->calculatorSecret = strtoupper(trim($this->calculatorSecret));
    }
}
