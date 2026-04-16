<?php

namespace Jsadways\OdooApi\Enums;

enum OdooEndpoint: string
{
    // 客戶/廠商/產品
    case PARTNER_CREATE = 'partner/create';
    case PARTNER_UPDATE = 'partner/update';
    case PRODUCT_CREATE = 'product/create';
    case PRODUCT_UPDATE = 'product/update';

    // 案件
    case CAMPAIGN_CREATE = 'campaign/create';
    case CAMPAIGN_UPDATE = 'campaign/update';

    // 財務-收入
    case INCOME_RECEIPT_LIST = 'income/receipt/list';
    case INCOME_DISCOUNT_LIST = 'income/discount/list';
    case INCOME_RECEIPT_CREATE = 'income/receipt/create';
    case INCOME_RECEIPT_DISCOUNT = 'income/receipt/discount';
    case INCOME_RECEIPT_VOID = 'income/receipt/void';
    case INCOME_RECEIPT_VOID_UPDATE = 'income/receipt/void/update';
    case INCOME_DISCOUNT_VOID = 'income/discount/void';

    // 財務-成本
    case COST_RECEIPT_LIST = 'cost/receipt/list';
    case COST_DISCOUNT_LIST = 'cost/discount/list';
    case COST_RECEIPT_DISCOUNT = 'cost/receipt/discount';
    case COST_DISCOUNT_VOID = 'cost/discount/void';

    public function method(): string
    {
        return match($this) {
            self::PARTNER_UPDATE,
            self::PRODUCT_UPDATE,
            self::CAMPAIGN_UPDATE => 'put',

            self::INCOME_RECEIPT_LIST,
            self::INCOME_DISCOUNT_LIST => 'get',

            default => 'post',
        };
    }
}
