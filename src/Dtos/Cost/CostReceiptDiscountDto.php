<?php

namespace Jsadways\OdooApi\Dtos\Cost;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class CostReceiptDiscountDto extends OdooPayloadDto
{
    /**
     * @param int $discount_type 1=單純折讓, 2=現折, 3=退佣, 4=現折+退佣
     * @param int $discount_paper_type 1=我方開立折讓單, 2=對方開立折讓單, 3=開立銷項發票, 4=我方開立Debit note, 5=收到Credit note
     * @param CostDiscountReceiptDto[] $receipt 發票折讓明細陣列
     * @param CostAllowanceDto|null $allowance
     * @param CostCreditNoteDto|null $credit_note
     * @param CostDebitNoteDto|null $debit_note
     * @param CostIncomeReceiptDto|null $income_receipt
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
        public readonly ?CostAllowanceDto $allowance = null,
        public readonly ?CostCreditNoteDto $credit_note = null,
        public readonly ?CostDebitNoteDto $debit_note = null,
        public readonly ?CostIncomeReceiptDto $income_receipt = null,
    ) {}

    public function get(): array
    {
        $data = parent::get();
        $data['receipt'] = array_map(fn (CostDiscountReceiptDto $r) => $r->get(), $this->receipt);
        if ($this->allowance) $data['allowance'] = $this->allowance->get();
        if ($this->credit_note) $data['credit_note'] = $this->credit_note->get();
        if ($this->debit_note) $data['debit_note'] = $this->debit_note->get();
        if ($this->income_receipt) $data['income_receipt'] = $this->income_receipt->get();
        return $data;
    }
}
