<?php

namespace App\Domain\Finance\Enums;

enum TransactionTypeEnum: int
{
    case INCOME = 1;
    case OUTCOME = 2;
    case TRANSFER_IN = 3;
    case TRANSFER_OUT = 4;
    case ADVANCE = 5;
    case REFUND = 6;
    case DIRECTOR_LOAN = 7;
    case DIRECTOR_WITHDRAWAL = 8;

    /**
     * Backward-compatible factory: accepts id or code string.
     */
    public static function fromCode(int|string|null $code): self
    {
        if ($code === null) {
            return self::INCOME;
        }

        // Numeric id -> direct enum value if exists
        if (is_numeric($code)) {
            $intCode = (int) $code;
            if ($enum = self::tryFrom($intCode)) {
                return $enum;
            }
        }

        // String code -> match by upper name
        $upper = strtoupper((string) $code);
        $map = [
            'INCOME' => self::INCOME,
            'OUTCOME' => self::OUTCOME,
            'EXPENSE' => self::OUTCOME, // legacy alias
            'TRANSFER_IN' => self::TRANSFER_IN,
            'TRANSFER_OUT' => self::TRANSFER_OUT,
            'ADVANCE' => self::ADVANCE,
            'REFUND' => self::REFUND,
            'DIRECTOR_LOAN' => self::DIRECTOR_LOAN,
            'DIRECTOR_WITHDRAWAL' => self::DIRECTOR_WITHDRAWAL,
        ];

        return $map[$upper] ?? self::INCOME;
    }

    public function code(): string
    {
        return $this->name;
    }

    public function sign(): int
    {
        return match ($this) {
            self::INCOME,
            self::TRANSFER_IN,
            self::ADVANCE,
            self::REFUND,
            self::DIRECTOR_LOAN => +1,

            self::OUTCOME,
            self::TRANSFER_OUT,
            self::DIRECTOR_WITHDRAWAL => -1,
        };
    }
}
