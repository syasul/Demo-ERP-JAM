<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Enums\GoodsIssueStatus;
use App\Models\GoodsIssue;
use App\Models\StockMovement;
use App\Services\Inventory\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class VoidGoodsIssue
{
    public function __construct(
        private readonly StockService $stockService
    ) {}

    public function handle(GoodsIssue $issue, string $reason): GoodsIssue
    {
        return DB::transaction(function () use ($issue, $reason): GoodsIssue {
            $issue->refresh();

            if ($issue->status === GoodsIssueStatus::VOID || $issue->status === GoodsIssueStatus::DRAFT) {
                throw new RuntimeException("Dokumen Pengeluaran status {$issue->status->label()} tidak dapat divoid.");
            }

            $movements = StockMovement::where('reference_type', $issue->getMorphClass())
                ->where('reference_id', $issue->id)
                ->where('direction', 'out')
                ->get();

            foreach ($movements as $movement) {
                $this->stockService->reverse($movement, "Void Goods Issue {$issue->number}: {$reason}");
            }

            $issue->status = GoodsIssueStatus::VOID;
            $issue->voided_by = Auth::id();
            $issue->voided_at = Carbon::now('Asia/Jakarta');
            $issue->voided_reason = $reason;
            $issue->save();

            activity('inventory')
                ->performedOn($issue)
                ->causedBy(Auth::user())
                ->log("Void Goods Issue {$issue->number}. Alasan: {$reason}");

            return $issue;
        });
    }
}
