<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecretPriceSetting extends Model
{
    protected $fillable = [
        'digit_0',
        'digit_1',
        'digit_2',
        'digit_3',
        'digit_4',
        'digit_5',
        'digit_6',
        'digit_7',
        'digit_8',
        'digit_9',
        'repeat_2',
        'repeat_3',
        'repeat_4',
        'repeat_5',
        'repeat_6',
        'repeat_7',
        'repeat_8',
    ];

    public static function singleton(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'digit_0' => 'K',
                'digit_1' => 'D',
                'digit_2' => 'A',
                'digit_3' => 'N',
                'digit_4' => 'C',
                'digit_5' => 'O',
                'digit_6' => 'W',
                'digit_7' => 'M',
                'digit_8' => 'I',
                'digit_9' => 'L',
                'repeat_2' => 'Z',
                'repeat_3' => 'X',
                'repeat_4' => 'W',
                'repeat_5' => 'V',
                'repeat_6' => 'VI',
                'repeat_7' => 'VII',
                'repeat_8' => 'VIII',
            ],
        );
    }

    /** @return array<string, string> */
    public function digitMap(): array
    {
        return [
            '0' => (string) $this->digit_0,
            '1' => (string) $this->digit_1,
            '2' => (string) $this->digit_2,
            '3' => (string) $this->digit_3,
            '4' => (string) $this->digit_4,
            '5' => (string) $this->digit_5,
            '6' => (string) $this->digit_6,
            '7' => (string) $this->digit_7,
            '8' => (string) $this->digit_8,
            '9' => (string) $this->digit_9,
        ];
    }

    /** @return array<int, string> */
    public function repeatMap(): array
    {
        return [
            2 => (string) $this->repeat_2,
            3 => (string) $this->repeat_3,
            4 => (string) $this->repeat_4,
            5 => (string) $this->repeat_5,
            6 => (string) $this->repeat_6,
            7 => (string) $this->repeat_7,
            8 => (string) $this->repeat_8,
        ];
    }
}
