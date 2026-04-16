<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeReceiptDiscountDto extends OdooPayloadDto
{
    /**
     * @param int $discount_type 1=單純折讓, 2=現折, 3=退佣, 4=現折+退佣
     * @param int $discount_paper_type 1=我方開立折讓單, 2=進項發票, 3=開立Credit note, 4=收到Debit note
     * @param IncomeDiscountReceiptDto[] $receipt 發票折讓明細陣列
     * @param IncomeCreditNoteDto|null $credit_note
     * @param IncomeDebitNoteDto|null $debit_note
     * @param IncomeCostReceiptDto|null $cost_receipt
     */
    public function __construct(
        public readonly string $discount_reason,
        public readonly string $discount_dt,
        public readonly string $finance_dt,
        public readonly int $discount_type,
        public readonly int $discount_paper_type,
        public readonly int|float $price,
        public readonly int|float $tax,
        public readonly int|float $total_price,
        public readonly array $receipt,
        public readonly string $creator_name,
        public readonly string $creator_email,
        public readonly ?IncomeCreditNoteDto $credit_note = null,
        public readonly ?IncomeDebitNoteDto $debit_note = null,
        public readonly ?IncomeCostReceiptDto $cost_receipt = null,
    ) {}

    public function get(): array
    {
        $data = parent::get();
        $data['receipt'] = array_map(fn (IncomeDiscountReceiptDto $r) => $r->get(), $this->receipt);
        if ($this->credit_note) $data['credit_note'] = $this->credit_note->get();
        if ($this->debit_note) $data['debit_note'] = $this->debit_note->get();
        if ($this->cost_receipt) $data['cost_receipt'] = $this->cost_receipt->get();
        return $data;
    }
}
