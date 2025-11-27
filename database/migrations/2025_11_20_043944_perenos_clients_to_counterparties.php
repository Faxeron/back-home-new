<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('legacy')
            ->table('clients')
            ->orderBy('id')
            ->chunkById(500, function ($clients): void {
                $counterparties = [];
                $individuals = [];

                foreach ($clients as $client) {
                    $fullName = trim(implode(' ', array_filter([
                        $client->last_name ?? '',
                        $client->first_name ?? '',
                        $client->middle_name ?? '',
                    ])));

                    $counterparties[] = [
                        'id' => $client->id,
                        'type' => 'individual',
                        'name' => $fullName !== '' ? $fullName : ($client->first_name ?? 'Неизвестно'),
                        'phone' => $client->phone ?? null,
                        'email' => $client->email ?? null,
                        'comment' => $client->comment ?? null,
                        'is_active' => true,
                        'created_at' => $client->created_at ?? now(),
                        'updated_at' => $client->updated_at ?? now(),
                    ];

                    $individuals[] = [
                        'counterparty_id' => $client->id,
                        'first_name' => $client->first_name ?? null,
                        'last_name' => $client->last_name ?? null,
                        'patronymic' => $client->middle_name ?? null,
                        'passport_number' => $client->passpor_numer ?? null,
                        'passport_code' => $client->passport_code ?? null,
                        'passport_whom' => $client->passport_whom ?? null,
                        'passport_address' => $client->passport_address ?? null,
                        'birth_date' => $client->passport_date ?? null,
                        'created_at' => $client->created_at ?? now(),
                        'updated_at' => $client->updated_at ?? now(),
                    ];
                }

                if ($counterparties) {
                    DB::connection('legacy_new')->table('counterparties')->insertOrIgnore($counterparties);
                }

                if ($individuals) {
                    DB::connection('legacy_new')->table('counterparty_individuals')->insertOrIgnore($individuals);
                }
            });
    }

    public function down(): void
    {
        DB::connection('legacy')
            ->table('clients')
            ->orderBy('id')
            ->chunkById(500, function ($clients): void {
                $ids = $clients->pluck('id')->all();

                DB::connection('legacy_new')->table('counterparty_individuals')->whereIn('counterparty_id', $ids)->delete();
                DB::connection('legacy_new')->table('counterparties')->whereIn('id', $ids)->delete();
            });
    }
};
