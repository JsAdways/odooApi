# OdooApi Package

Laravel package，封裝與 Odoo 系統的 API 溝通。透過 `Odoo` Facade 搭配 `OdooEndpoint` enum 與強型別 DTO 呼叫各 endpoint。

API 文件來源：`http://{ODOO_SERVER_HOST}/help?format=json`

---

## 基本用法

```php
use Jsadways\OdooApi\Facades\Odoo;
use Jsadways\OdooApi\Enums\OdooEndpoint;
use Jsadways\OdooApi\Dtos\Campaign\CampaignCreateDto;
use Jsadways\OdooApi\Dtos\Campaign\CampaignCueDto;

$result = Odoo::create(
    OdooEndpoint::CAMPAIGN_CREATE,
    new CampaignCreateDto(
        organization: 1,
        name: 'Apple Q126 宣傳_202511-12',
        client_id: 53,
        campaign_number: '2511000',
        start_dt: '2025-10-28',
        end_dt: '2026-11-19',
        price: 100000,
        tax: 5000,
        total_price: 105000,
        status: 1,
        exchange_rate: 1,
        cue: [
            new CampaignCueDto(
                cue_number: 315,
                product_id: 69,
                budget: 1999,
                month: '202510',
                income: 20000,
                income_total: 16600,
                income_discount: 2000,
                income_rebate: 1400,
                cost: 5000,
                cost_total: 4850,
                cost_discount: 50,
                cost_rebate: 100,
                profit_code: '03651',
                cost_code: '075521',
            ),
        ],
    )
);
```

Facade 提供三個方法，對應不同操作類型：

| 方法 | 說明 |
|------|------|
| `Odoo::create(OdooEndpoint, OdooPayloadDto)` | 建立資料 |
| `Odoo::update(OdooEndpoint, OdooPayloadDto)` | 更新資料 |
| `Odoo::list(OdooEndpoint, OdooPayloadDto)` | 查詢列表 |
| `Odoo::retry()` | 重試所有失敗的待處理請求 |

---

## Endpoint 與 DTO 對照表

### 客戶 / 廠商

| Endpoint | Method | DTO |
|----------|--------|-----|
| `PARTNER_CREATE` | POST | `Partner\PartnerCreateDto` |
| `PARTNER_UPDATE` | PUT | `Partner\PartnerUpdateDto` |

```php
// 建立
Odoo::create(OdooEndpoint::PARTNER_CREATE, new PartnerCreateDto(
    type: 1,               // 1=客戶, 2=供應商
    name: 'AA廣告有限公司',
    short_name: 'AA廣告',
    en_name: 'AA INTEGRATION CO., LTD',
    id_number: '80666612',
    address: '台北市信義區...',
    tel: '(02)22226666',
    finance_email: null,   // 可選
));

// 更新（多一個 id）
Odoo::update(OdooEndpoint::PARTNER_UPDATE, new PartnerUpdateDto(
    id: 267,
    type: 1,
    name: 'AA廣告有限公司',
    // ... 同 create 欄位
));
```

### 產品

| Endpoint | Method | DTO |
|----------|--------|-----|
| `PRODUCT_CREATE` | POST | `Product\ProductCreateDto` |
| `PRODUCT_UPDATE` | PUT | `Product\ProductUpdateDto` |

```php
// 建立
Odoo::create(OdooEndpoint::PRODUCT_CREATE, new ProductCreateDto(
    name: '媒體_Meta_服務費',
    memo: '',              // 可選
));

// 更新（支援多筆，data 為陣列）
Odoo::update(OdooEndpoint::PRODUCT_UPDATE, new ProductUpdateDto(
    products: [
        ['id' => 158, 'name' => '媒體_Meta_服務費', 'memo' => ''],
        ['id' => 159, 'name' => '媒體_Meta_上刊費', 'memo' => ''],
    ]
));
```

### 案件

| Endpoint | Method | DTO | 子 DTO |
|----------|--------|-----|--------|
| `CAMPAIGN_CREATE` | POST | `Campaign\CampaignCreateDto` | `CampaignCueDto` |
| `CAMPAIGN_UPDATE` | PUT | `Campaign\CampaignUpdateDto` | `CampaignCueDto` |

**CampaignCueDto 欄位：**

| 欄位 | 型別 | 說明 |
|------|------|------|
| cue_number | int | Cue 編號 |
| product_id | int | 產品 ID |
| budget | int\|float | 預算 |
| month | string | 月份（YYYYMM） |
| income | int\|float | 收入 |
| income_total | int\|float | 收入合計 |
| income_discount | int\|float | 收入折讓 |
| income_rebate | int\|float | 收入退佣 |
| cost | int\|float | 成本 |
| cost_total | int\|float | 成本合計 |
| cost_discount | int\|float | 成本折讓 |
| cost_rebate | int\|float | 成本退佣 |
| profit_code | string | 損益碼 |
| cost_code | string | 成本碼 |
| id | ?int | Odoo ID（update 時帶入表示更新既有 cue，不帶則新建） |

### 財務 - 收入

| Endpoint | Method | DTO | 子 DTO |
|----------|--------|-----|--------|
| `INCOME_RECEIPT_LIST` | GET | `Income\IncomeReceiptListDto` | — |
| `INCOME_DISCOUNT_LIST` | GET | `Income\IncomeDiscountListDto` | — |
| `INCOME_RECEIPT_CREATE` | POST | `Income\IncomeReceiptCreateDto` | `IncomeReceiptCueDto`, `NotificationEmailDto` |
| `INCOME_RECEIPT_DISCOUNT` | POST | `Income\IncomeReceiptDiscountDto` | `IncomeDiscountReceiptDto`, `IncomeDiscountReceiptCueDto`, `IncomeCreditNoteDto`, `IncomeDebitNoteDto`, `IncomeCostReceiptDto` |
| `INCOME_RECEIPT_VOID` | POST | `Income\IncomeReceiptVoidDto` | — |
| `INCOME_RECEIPT_VOID_UPDATE` | POST | `Income\IncomeReceiptVoidUpdateDto` | — |
| `INCOME_DISCOUNT_VOID` | POST | `Income\IncomeDiscountVoidDto` | — |

#### 查詢列表

```php
Odoo::list(OdooEndpoint::INCOME_RECEIPT_LIST, new IncomeReceiptListDto(
    filter: [
        'keyword' => '關鍵字',
        'campaign_id' => [53, 61],
        'partner_id' => [871],
        'receipt_number' => ['KK88909876'],
        'receipt_date_start' => '20250101',
        'receipt_date_end' => '20250301',
    ],
    order_by: 'receipt_date', // 可選
    order: 'DESC',            // 可選
    page: 1,                  // 可選
    per_page: 30,             // 可選
));
```

#### 建立收入發票草稿

```php
Odoo::create(OdooEndpoint::INCOME_RECEIPT_CREATE, new IncomeReceiptCreateDto(
    campaign_id: 861,
    receipt_type: 1,       // 1=電子發票三聯, 2=二聯, 3=國外Invoice
    dollar_type: 1,        // 1=新台幣, 2=美金, 3=日幣
    item_name: '廣告服務費',
    price: 100000,
    tax: 5000,
    total_price: 105000,
    cue: [
        new IncomeReceiptCueDto(id: 871, finance_income: 80000, offseted_price: 20000, offset_price: 60000),
        new IncomeReceiptCueDto(id: 872, finance_income: 60000, offseted_price: 20000, offset_price: 40000),
    ],
    notification_email: [
        new NotificationEmailDto(name: 'Abbie', email: 'abbie@js-adways.com.tw'),
    ],
    creator_name: 'Lex',
    creator_email: 'lex@js-adways.com.tw',
    urgent: false,             // 可選，預設 false
    memo: '備註',              // 可選
    specify_dt: '20250101',    // 可選
    pickup_dt: '20250101',     // 可選
));
```

#### 建立收入發票折讓草稿

```php
Odoo::create(OdooEndpoint::INCOME_RECEIPT_DISCOUNT, new IncomeReceiptDiscountDto(
    discount_reason: '退佣',
    discount_dt: '20260113',
    finance_dt: '20260113',
    discount_type: 1,          // 1=單純折讓, 2=現折, 3=退佣, 4=現折+退佣
    discount_paper_type: 1,    // 1=我方開立折讓單, 2=進項發票, 3=開立Credit note, 4=收到Debit note
    price: 100000,
    tax: 5000,
    total_price: 105000,
    receipt: [
        new IncomeDiscountReceiptDto(
            id: 573,
            discount_price: 5000,
            cue: [
                new IncomeDiscountReceiptCueDto(id: 6288, discount_price: 3000),
                new IncomeDiscountReceiptCueDto(id: 6289, discount_price: 2000),
            ],
        ),
    ],
    creator_name: 'Lex',
    creator_email: 'lex@js-adways.com.tw',
    credit_note: new IncomeCreditNoteDto(
        dollar_type: 1,
        item_name: '折讓',
        credit_note_number: 'A784TYG887',
        date: '20260113',
        contact_name: '王小明',
        contact_address: '台北市信義區...',
        memo: '',
    ),
    debit_note: new IncomeDebitNoteDto(dollar_type: 1, item_name: '折讓', debit_note_number: 'A784TYG887', file: ''),
    cost_receipt: new IncomeCostReceiptDto(receipt_dt: '20260105', item_name: '退佣金', receipt_number: 'AA88765432', file: ''),
));
```

#### 作廢

```php
// 發票作廢
Odoo::create(OdooEndpoint::INCOME_RECEIPT_VOID, new IncomeReceiptVoidDto(
    id: 751,
    reason: '開錯金額',
    creator_name: 'Lex',
    creator_email: 'lex@js-adways.com.tw',
    reply_received: false, // 可選，預設 false
    file: '',              // 可選
    reply_file: '',        // 可選
));

// 收到客戶回覆後更新作廢資料
Odoo::update(OdooEndpoint::INCOME_RECEIPT_VOID_UPDATE, new IncomeReceiptVoidUpdateDto(
    id: 751,
    reply_received: true,
    reply_file: '',        // 可選
));

// 折讓作廢
Odoo::create(OdooEndpoint::INCOME_DISCOUNT_VOID, new IncomeDiscountVoidDto(
    id: 27,
    reason: '客戶不收',
    creator_name: 'Lex',
    creator_email: 'lex@js-adways.com.tw',
    file: '',              // 可選
));
```

### 財務 - 成本

| Endpoint | Method | DTO | 子 DTO |
|----------|--------|-----|--------|
| `COST_RECEIPT_LIST` | POST | `Cost\CostReceiptListDto` | — |
| `COST_DISCOUNT_LIST` | POST | `Cost\CostDiscountListDto` | — |
| `COST_RECEIPT_DISCOUNT` | POST | `Cost\CostReceiptDiscountDto` | `CostDiscountReceiptDto`, `CostDiscountReceiptCueDto`, `CostAllowanceDto`, `CostCreditNoteDto`, `CostDebitNoteDto`, `CostIncomeReceiptDto` |
| `COST_DISCOUNT_VOID` | POST | `Cost\CostDiscountVoidDto` | — |

#### 建立成本發票折讓草稿

```php
Odoo::create(OdooEndpoint::COST_RECEIPT_DISCOUNT, new CostReceiptDiscountDto(
    discount_reason: '退佣',
    discount_dt: '20260113',
    finance_dt: '20260113',
    discount_type: 1,          // 1=單純折讓, 2=現折, 3=退佣, 4=現折+退佣
    discount_paper_type: 1,    // 1=我方開立折讓單, 2=對方開立, 3=開立銷項發票, 4=我方開立Debit note, 5=收到Credit note
    price: 100000,
    tax: 5000,
    total_price: 105000,
    receipt: [
        new CostDiscountReceiptDto(
            id: 6311,
            discount_price: 5000,
            cue: [
                new CostDiscountReceiptCueDto(id: 316, discount_price: 3000),
                new CostDiscountReceiptCueDto(id: 741, discount_price: 2000),
            ],
        ),
    ],
    creator_name: 'Lex',
    creator_email: 'lex@js-adways.com.tw',
    allowance: new CostAllowanceDto(file: ''),
    credit_note: new CostCreditNoteDto(file: ''),
    debit_note: new CostDebitNoteDto(
        dollar_type: 1,
        item_name: '折讓',
        debit_note_number: 'A784TYG887',
        date: '20260113',
        contact_name: '王小明',
        contact_address: '台北市信義區...',
        memo: '',
    ),
    income_receipt: new CostIncomeReceiptDto(receipt_dt: '20260105', item_name: '退佣金', receipt_number: 'AA88765432', file: ''),
));
```

---

## DTO 目錄結構

```
src/Dtos/
├── OdooPayloadDto.php                     # 抽象基底類別
├── Partner/
│   ├── PartnerCreateDto.php
│   └── PartnerUpdateDto.php
├── Product/
│   ├── ProductCreateDto.php
│   └── ProductUpdateDto.php               # get() 回傳 sequential array
├── Campaign/
│   ├── CampaignCueDto.php                 # 子訂單
│   ├── CampaignCreateDto.php
│   └── CampaignUpdateDto.php
├── Income/
│   ├── IncomeReceiptListDto.php
│   ├── IncomeDiscountListDto.php
│   ├── IncomeReceiptCreateDto.php
│   ├── IncomeReceiptCueDto.php            # 發票 cue 沖銷
│   ├── NotificationEmailDto.php           # 通知信箱
│   ├── IncomeReceiptDiscountDto.php
│   ├── IncomeDiscountReceiptDto.php       # 折讓發票明細
│   ├── IncomeDiscountReceiptCueDto.php    # 折讓發票 cue
│   ├── IncomeCreditNoteDto.php            # 折讓單
│   ├── IncomeDebitNoteDto.php             # Debit note
│   ├── IncomeCostReceiptDto.php           # 退佣進項發票
│   ├── IncomeReceiptVoidDto.php
│   ├── IncomeReceiptVoidUpdateDto.php    # 作廢資料補件（收到客戶回覆）
│   └── IncomeDiscountVoidDto.php
└── Cost/
    ├── CostReceiptListDto.php
    ├── CostDiscountListDto.php
    ├── CostReceiptDiscountDto.php
    ├── CostDiscountReceiptDto.php         # 折讓發票明細
    ├── CostDiscountReceiptCueDto.php      # 折讓發票 cue
    ├── CostAllowanceDto.php               # 折讓單附件
    ├── CostCreditNoteDto.php              # Credit note
    ├── CostDebitNoteDto.php               # Debit note
    ├── CostIncomeReceiptDto.php           # 退佣銷項發票
    └── CostDiscountVoidDto.php
```

## Retry 機制

每次呼叫 `create` / `update` / `list` 時，請求資料會先寫入 Cache。成功後自動移除，失敗則保留等待 retry。

透過索引 key（`odoo_pending_requests`）記錄所有待 retry 的 cache key，retry 時依 `cached_at` 時間排序，先快取的先處理。

### 手動 retry

```php
$results = Odoo::retry();
```

回傳陣列，每筆包含 `transaction_key`、`success`、`result`。

### 自動排程 retry

在 `.env` 中設定：

```
ODOO_AUTO_RETRY=true
```

啟用後，套件會自動註冊每小時執行一次的排程進行 retry。預設為 `false`（不啟用）。

主專案需確保 cron 有執行 `schedule:run`：

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 設計說明

- 所有 DTO 繼承 `OdooPayloadDto`，提供 `get(): array` 方法將 properties 轉為 array
- `get()` 自動過濾值為 `null` 的欄位（可選欄位不傳時不會出現在 payload 中）
- 巢狀物件皆有獨立 DTO，IDE 可完整提示每個欄位
- `ProductUpdateDto` 覆寫 `get()` 回傳 sequential array，對應 API data 為陣列的格式
- `OdooProcess` 負責快取管理與 retry 邏輯，`OdooService` 僅做薄層串接
- 失敗請求透過索引表機制追蹤，支援手動或排程自動 retry
