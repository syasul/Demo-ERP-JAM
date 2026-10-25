<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\DocumentSequence;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DocumentNumberService
{
    /**
     * Generate the next document number for the given document type.
     */
    public function next(string $docType, ?DateTimeInterface $date = null): string
    {
        $date = $date ? Carbon::instance($date) : Carbon::now('Asia/Jakarta');
        $resetMode = (string) config('erp.sequence_reset', 'monthly');

        $year = $date->format('Y');
        $month = $date->format('m');
        $period = $resetMode === 'yearly' ? $year : $year.$month;

        /** @var string $prefix */
        $prefix = config("erp.doc_prefix.{$docType}", strtoupper($docType));

        /** @var int $nextNumber */
        $nextNumber = DB::transaction(function () use ($docType, $period): int {
            /** @var DocumentSequence|null $sequence */
            $sequence = DocumentSequence::where('doc_type', $docType)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                DocumentSequence::create([
                    'doc_type' => $docType,
                    'period' => $period,
                    'last_number' => 1,
                ]);

                return 1;
            }

            $sequence->last_number++;
            $sequence->save();

            return (int) $sequence->last_number;
        });

        $format = (string) config('erp.doc_number_format', '{PREFIX}/{YYYY}/{MM}/{SEQ:4}');

        return (string) preg_replace_callback('/\{SEQ:?(\d+)?\}/', function (array $matches) use ($nextNumber): string {
            $digits = isset($matches[1]) ? (int) $matches[1] : 4;

            return str_pad((string) $nextNumber, $digits, '0', STR_PAD_LEFT);
        }, str_replace(
            ['{PREFIX}', '{YYYY}', '{MM}'],
            [$prefix, $year, $month],
            $format
        ));
    }
}
