<?php

namespace Jsadways\OdooApi\Tests\Feature\Live;

use Jsadways\OdooApi\Dtos\Campaign\CampaignCreateDto;
use Jsadways\OdooApi\Dtos\Campaign\CampaignCueDto;
use Jsadways\OdooApi\Dtos\Campaign\CampaignUpdateDto;
use Jsadways\OdooApi\Dtos\Cost\CostAllowanceDto;
use Jsadways\OdooApi\Dtos\Cost\CostCreditNoteDto;
use Jsadways\OdooApi\Dtos\Cost\CostDebitNoteDto;
use Jsadways\OdooApi\Dtos\Cost\CostDiscountListDto;
use Jsadways\OdooApi\Dtos\Cost\CostDiscountReceiptCueDto;
use Jsadways\OdooApi\Dtos\Cost\CostDiscountReceiptDto;
use Jsadways\OdooApi\Dtos\Cost\CostDiscountVoidDto;
use Jsadways\OdooApi\Dtos\Cost\CostIncomeReceiptDto;
use Jsadways\OdooApi\Dtos\Cost\CostReceiptDiscountDto;
use Jsadways\OdooApi\Dtos\Cost\CostReceiptListDto;
use Jsadways\OdooApi\Dtos\Income\IncomeCostReceiptDto;
use Jsadways\OdooApi\Dtos\Income\IncomeCreditNoteDto;
use Jsadways\OdooApi\Dtos\Income\IncomeDebitNoteDto;
use Jsadways\OdooApi\Dtos\Income\IncomeDiscountListDto;
use Jsadways\OdooApi\Dtos\Income\IncomeDiscountReceiptCueDto;
use Jsadways\OdooApi\Dtos\Income\IncomeDiscountReceiptDto;
use Jsadways\OdooApi\Dtos\Income\IncomeDiscountVoidDto;
use Jsadways\OdooApi\Dtos\Income\IncomeReceiptCreateDto;
use Jsadways\OdooApi\Dtos\Income\IncomeReceiptCueDto;
use Jsadways\OdooApi\Dtos\Income\IncomeReceiptDiscountDto;
use Jsadways\OdooApi\Dtos\Income\IncomeReceiptListDto;
use Jsadways\OdooApi\Dtos\Income\IncomeReceiptVoidDto;
use Jsadways\OdooApi\Dtos\Income\IncomeReceiptVoidUpdateDto;
use Jsadways\OdooApi\Dtos\Income\NotificationEmailDto;
use Jsadways\OdooApi\Dtos\Partner\PartnerCreateDto;
use Jsadways\OdooApi\Dtos\Partner\PartnerUpdateDto;
use Jsadways\OdooApi\Dtos\Product\ProductCreateDto;
use Jsadways\OdooApi\Dtos\Product\ProductUpdateDto;
use Jsadways\OdooApi\Enums\OdooEndpoint;
use Jsadways\OdooApi\Facades\Odoo;
use Jsadways\OdooApi\Tests\OdooLiveTestCase;

/**
 * 完整 18 步 Odoo 整合測試流程。
 *
 * 所有測試打的是 staging 環境，資料不清除以利人工到 Odoo 後台對帳。
 * 設定 ODOO_SERVER_HOST / ODOO_USER / ODOO_PASSWORD 後執行：
 *
 *   php artisan test --group=live
 *
 * @group live
 */
class OdooFullFlowTest extends OdooLiveTestCase
{
    /**
     * 跨 test method 傳遞 staging 上建立的真實 id。
     * @var array<string, mixed>
     */
    protected static array $ctx = [];

    // ─────────────────────────────────────────────────────────────
    // Step 1：建立 4 個客戶
    // ─────────────────────────────────────────────────────────────
    public function test_01_partner_create(): void
    {
        // 前 3 筆為客戶(type=1)，第 4 筆 DD 改為供應商(type=2)，供 campaign cue.vendor_id 使用
        $configs = [
            ['name' => 'AA廣告有限公司',     'short' => 'AA廣告', 'en' => 'AA INTEGRATION CO., LTD',     'tax' => '80666612', 'type' => 1],
            ['name' => 'BB行銷股份有限公司', 'short' => 'BB行銷', 'en' => 'BB MARKETING CO., LTD',       'tax' => '80666613', 'type' => 1],
            ['name' => 'CC媒體企業有限公司', 'short' => 'CC媒體', 'en' => 'CC MEDIA ENTERPRISE LTD.',    'tax' => '80666614', 'type' => 1],
            ['name' => 'DD數位整合有限公司', 'short' => 'DD數位', 'en' => 'DD DIGITAL INTEGRATION LTD',  'tax' => '80666615', 'type' => 2],
        ];

        $partnerIds = [];
        foreach ($configs as $cfg) {
            $result = Odoo::Create(
                OdooEndpoint::PARTNER_CREATE,
                new PartnerCreateDto(
                    type: $cfg['type'],
                    name: $cfg['name'],
                    short_name: $cfg['short'],
                    en_name: $cfg['en'],
                    id_number: $cfg['tax'],
                    address: '台北市信義區忠孝東路260巷24號',
                    tel: '(02)22226666',
                    finance_email: null,
                )
            );
            $this->assertOdooSuccess($result, "PARTNER_CREATE({$cfg['name']})");
            $this->assertArrayHasKey('id', $result['data']);
            $partnerIds[] = $result['data']['id'];
        }
        $this->assertCount(4, $partnerIds);

        self::$ctx['partner_ids'] = $partnerIds;
        self::$ctx['partner_id'] = $partnerIds[0]; // 第一筆 alias，供 step 2 / 11 等沿用
        self::$ctx['vendor_id'] = $partnerIds[3];  // DD (type=2) 供 cue.vendor_id 使用
    }

    // ─────────────────────────────────────────────────────────────
    // Step 2：用 step 1 的 id 編輯客戶
    // ─────────────────────────────────────────────────────────────
    /** @depends test_01_partner_create */
    public function test_02_partner_update(): void
    {
        $result = Odoo::Update(
            OdooEndpoint::PARTNER_UPDATE,
            new PartnerUpdateDto(
                id: self::$ctx['partner_id'],
                type: 1,
                name: 'AA廣告有限公司(已更新)',
                short_name: 'AA廣告',
                en_name: 'AA INTEGRATION CO., LTD',
                id_number: '80666612',
                address: '台北市信義區忠孝東路260巷24號5樓',
                tel: '(02)22226666',
                finance_email: null,
            )
        );
        $this->assertOdooSuccess($result, 'PARTNER_UPDATE');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 3：建立 5 筆 product
    // ─────────────────────────────────────────────────────────────
    public function test_03_product_create(): void
    {
        $names = [
            '媒體_Meta_服務費',
            '媒體_Meta_上刊費',
            '媒體_Meta_設計費',
            '媒體_Meta_曝光費',
            '媒體_Meta_點擊費',
        ];

        $productIds = [];
        foreach ($names as $name) {
            $result = Odoo::Create(
                OdooEndpoint::PRODUCT_CREATE,
                new ProductCreateDto(name: $name, memo: '')
            );
            $this->assertOdooSuccess($result, "PRODUCT_CREATE($name)");
            $this->assertArrayHasKey('id', $result['data']);
            $productIds[] = $result['data']['id'];
        }
        $this->assertCount(5, $productIds);

        self::$ctx['product_ids'] = $productIds;
    }

    // ─────────────────────────────────────────────────────────────
    // Step 4：用 step 3 的兩個 id 進行多筆更新
    // ─────────────────────────────────────────────────────────────
    /** @depends test_03_product_create */
    public function test_04_product_update(): void
    {
        $products = [];
        foreach (self::$ctx['product_ids'] as $i => $pid) {
            $products[] = ['id' => $pid, 'name' => '媒體_Meta_產品'.($i + 1).'(已更新)', 'memo' => ''];
        }
        $result = Odoo::Update(
            OdooEndpoint::PRODUCT_UPDATE,
            new ProductUpdateDto(products: $products)
        );
        $this->assertOdooSuccess($result, 'PRODUCT_UPDATE (5筆)');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 5：建立 3 個 campaign
    //   #1：客戶1 + 產品1, 產品2
    //   #2：客戶2 + 產品2, 產品3
    //   #3：客戶3 + 產品3, 產品4
    // ─────────────────────────────────────────────────────────────
    /**
     * @depends test_01_partner_create
     * @depends test_03_product_create
     */
    public function test_05_campaign_create(): void
    {
        $campaignConfigs = [
            ['partner_idx' => 0, 'product_idxs' => [0, 1]],
            ['partner_idx' => 1, 'product_idxs' => [1, 2]],
            ['partner_idx' => 2, 'product_idxs' => [2, 3]],
        ];

        $campaignIds = [];
        $cueIds = []; // 2D: [campaign_idx][cue_idx]
        $stamp = date('YmdHis');

        foreach ($campaignConfigs as $i => $cfg) {
            $cues = [];
            foreach ($cfg['product_idxs'] as $j => $prodIdx) {
                $cues[] = new CampaignCueDto(
                    cue_number: (string) (1000 + ($i * 10) + $j + 1),
                    type: 1,
                    product_id: self::$ctx['product_ids'][$prodIdx],
                    vendor_id: self::$ctx['vendor_id'],
                    budget: 50000,
                    month: '20251'.$j, // 202510, 202511
                    income: 50000, income_total: 47500, income_discount: 1500, income_rebate: 1000,
                    cost: 30000, cost_total: 29500, cost_discount: 300, cost_rebate: 200,
                    profit_code: '03651', cost_code: '075521',
                );
            }
            $result = Odoo::Create(
                OdooEndpoint::CAMPAIGN_CREATE,
                new CampaignCreateDto(
                    organization: 1,
                    name: 'Live Test Campaign #'.($i + 1).' '.$stamp,
                    ae_name: 'Live Test AE',
                    client_id: self::$ctx['partner_ids'][$cfg['partner_idx']],
                    campaign_number: 'LT'.($i + 1).$stamp,
                    start_dt: '2025-10-28',
                    end_dt: '2026-11-19',
                    price: 100000,
                    tax: 5000,
                    total_price: 105000,
                    status: 1,
                    exchange_rate: 1,
                    cue: $cues,
                    client_contact_email: 'alvin@js-adways.com.tw',
                    message: null,
                    memo: null,
                )
            );
            $this->assertOdooSuccess($result, 'CAMPAIGN_CREATE #'.($i + 1));
            $this->assertArrayHasKey('id', $result['data']);
            $this->assertArrayHasKey('cue', $result['data']);
            $this->assertNotEmpty($result['data']['cue']);

            $campaignIds[] = $result['data']['id'];
            $cueIds[] = array_map(fn ($c) => $c['id'], $result['data']['cue']);
        }
        $this->assertCount(3, $campaignIds);

        self::$ctx['campaign_ids'] = $campaignIds;
        self::$ctx['campaign_id'] = $campaignIds[0]; // 第一筆 alias
        self::$ctx['cue_ids'] = $cueIds; // 2D
    }

    // ─────────────────────────────────────────────────────────────
    // Step 6a：campaign 更新 — cue 不帶 id（新增子訂單）
    // ─────────────────────────────────────────────────────────────
    /** @depends test_05_campaign_create */
    public function test_06a_campaign_update_new_cue(): void
    {
        [$pid1] = self::$ctx['product_ids'];
        $result = Odoo::Update(
            OdooEndpoint::CAMPAIGN_UPDATE,
            new CampaignUpdateDto(
                id: self::$ctx['campaign_id'],
                organization: 1,
                name: 'Live Test Campaign (06a new cue)',
                ae_name: 'Live Test AE',
                client_id: self::$ctx['partner_id'],
                campaign_number: 'LT'.date('YmdHis'),
                start_dt: '2025-10-28',
                end_dt: '2026-11-19',
                price: 150000,
                tax: 7500,
                total_price: 157500,
                status: 1,
                exchange_rate: 1,
                cue: [
                    new CampaignCueDto(
                        cue_number: '1003', type: 1, product_id: $pid1, vendor_id: self::$ctx['vendor_id'],
                        budget: 50000, month: '202512',
                        income: 50000, income_total: 47500, income_discount: 1500, income_rebate: 1000,
                        cost: 30000, cost_total: 29500, cost_discount: 300, cost_rebate: 200,
                        profit_code: '03651', cost_code: '075521',
                    ),
                ],
                client_contact_email: 'alvin@js-adways.com.tw',
                message: null,
                memo: null,
            )
        );
        $this->assertOdooSuccess($result, 'CAMPAIGN_UPDATE (new cue)');
        if (!empty($result['data']['cue'])) {
            $existing = self::$ctx['cue_ids'][0] ?? [];
            foreach ($result['data']['cue'] as $c) {
                if (!in_array($c['id'], $existing, true)) {
                    self::$ctx['cue_ids'][0][] = $c['id'];
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Step 6b：campaign 更新 — cue 帶現有 id（更新既有子訂單）
    // ─────────────────────────────────────────────────────────────
    /** @depends test_05_campaign_create */
    public function test_06b_campaign_update_existing_cue(): void
    {
        [$pid1, $pid2] = self::$ctx['product_ids'];
        $cueIds = self::$ctx['cue_ids'][0]; // 第一個 campaign 的 cue list
        $result = Odoo::Update(
            OdooEndpoint::CAMPAIGN_UPDATE,
            new CampaignUpdateDto(
                id: self::$ctx['campaign_id'],
                organization: 1,
                name: 'Live Test Campaign (06b update cue)',
                ae_name: 'Live Test AE',
                client_id: self::$ctx['partner_id'],
                campaign_number: 'LT'.date('YmdHis'),
                start_dt: '2025-10-28',
                end_dt: '2026-11-19',
                price: 100000,
                tax: 5000,
                total_price: 105000,
                status: 1,
                exchange_rate: 1,
                cue: [
                    new CampaignCueDto(
                        id: $cueIds[0],
                        cue_number: '1001', type: 1, product_id: $pid1, vendor_id: self::$ctx['vendor_id'],
                        budget: 60000, month: '202510',
                        income: 60000, income_total: 57500, income_discount: 1500, income_rebate: 1000,
                        cost: 30000, cost_total: 29500, cost_discount: 300, cost_rebate: 200,
                        profit_code: '03651', cost_code: '075521',
                    ),
                    new CampaignCueDto(
                        id: $cueIds[1],
                        cue_number: '1002', type: 1, product_id: $pid2, vendor_id: self::$ctx['vendor_id'],
                        budget: 40000, month: '202511',
                        income: 40000, income_total: 37500, income_discount: 1500, income_rebate: 1000,
                        cost: 30000, cost_total: 29500, cost_discount: 300, cost_rebate: 200,
                        profit_code: '03651', cost_code: '075521',
                    ),
                ],
                client_contact_email: 'alvin@js-adways.com.tw',
                message: null,
                memo: null,
            )
        );
        $this->assertOdooSuccess($result, 'CAMPAIGN_UPDATE (existing cue)');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 7：每個 campaign 建立 1 張收入發票，共 3 張
    // ─────────────────────────────────────────────────────────────
    /** @depends test_05_campaign_create */
    public function test_07_income_receipt_create_per_campaign(): void
    {
        $receiptIds = [];
        foreach (self::$ctx['campaign_ids'] as $i => $campaignId) {
            $cueId = self::$ctx['cue_ids'][$i][0]; // 該 campaign 的第一個 cue
            $result = Odoo::Create(
                OdooEndpoint::INCOME_RECEIPT_CREATE,
                new IncomeReceiptCreateDto(
                    campaign_id: $campaignId,
                    receipt_type: 1,
                    dollar_type: 1,
                    item_name: '廣告服務費 (Campaign #'.($i + 1).')',
                    price: 10000,
                    tax: 500,
                    total_price: 10500,
                    cue: [
                        new IncomeReceiptCueDto(
                            id: $cueId, finance_income: 10000, offseted_price: 0, offset_price: 10000,
                        ),
                    ],
                    notification_email: [
                        new NotificationEmailDto(name: 'Abbie', email: 'abbie@js-adways.com.tw'),
                    ],
                    creator_name: 'LiveTest',
                    creator_email: 'livetest@js-adways.com.tw',
                    urgent: false,
                    memo: 'Live test receipt for campaign #'.($i + 1),
                    specify_dt: '20250101',
                    pickup_dt: '20250101',
                )
            );
            $this->assertOdooSuccess($result, 'INCOME_RECEIPT_CREATE (campaign #'.($i + 1).')');
            $this->assertArrayHasKey('id', $result['data']);
            $receiptIds[] = $result['data']['id'];
        }

        $this->assertCount(3, $receiptIds);
        self::$ctx['receipt_ids'] = $receiptIds;
    }

    // ─────────────────────────────────────────────────────────────
    // Step 8：建立收入折讓 — 涵蓋 discount_type 1~4 × discount_paper_type 1~4
    // 各 variant 對到一個 campaign（08d 因 type4×paper4 而 reuse campaign 1）
    // ─────────────────────────────────────────────────────────────
    private function _buildIncomeDiscount(int $discountType, int $paperType, int $campaignIdx, string $label): void
    {
        $receiptId = self::$ctx['receipt_ids'][$campaignIdx];
        $cueId = self::$ctx['cue_ids'][$campaignIdx][0];

        $result = Odoo::Create(
            OdooEndpoint::INCOME_RECEIPT_DISCOUNT,
            new IncomeReceiptDiscountDto(
                discount_reason: '測試折讓 '.$label,
                discount_dt: '20260113',
                finance_dt: '20260113',
                discount_type: $discountType,
                discount_paper_type: $paperType,
                price: 5000,
                tax: 250,
                total_price: 5250,
                receipt: [
                    new IncomeDiscountReceiptDto(
                        id: $receiptId,
                        discount_price: 5000,
                        cue: [
                            new IncomeDiscountReceiptCueDto(id: $cueId, discount_price: 5000),
                        ],
                    ),
                ],
                creator_name: 'LiveTest',
                creator_email: 'livetest@js-adways.com.tw',
                credit_note: new IncomeCreditNoteDto(
                    dollar_type: 1, item_name: '折讓', credit_note_number: 'CN'.$label,
                    date: '20260113', contact_name: '王小明',
                    contact_address: '台北市信義區忠孝東路260巷24號5樓', memo: '',
                ),
                debit_note: new IncomeDebitNoteDto(
                    dollar_type: 1, item_name: '折讓', debit_note_number: 'DN'.$label, file: '',
                ),
                cost_receipt: new IncomeCostReceiptDto(
                    receipt_dt: '20260105', item_name: '退佣金',
                    receipt_number: 'CR'.$label,
                    price: 1000, tax: 50, total_price: 1050,
                ),
            )
        );
        $this->assertOdooSuccess($result, "INCOME_RECEIPT_DISCOUNT $label");
        $this->assertArrayHasKey('id', $result['data']);

        self::$ctx['income_discount_ids'] ??= [];
        self::$ctx['income_discount_ids'][$label] = $result['data']['id'];

        // 存 metadata 給 step 11 推算「我們建的這幾筆裡符合 filter 的有哪些」
        // 註：step 5 設定下，campaign_idx == partner_idx
        self::$ctx['income_discount_meta'] ??= [];
        self::$ctx['income_discount_meta'][$label] = [
            'id' => $result['data']['id'],
            'campaign_idx' => $campaignIdx,
            'partner_idx' => $campaignIdx,
            'discount_type' => $discountType,
            'discount_paper_type' => $paperType,
            'receipt_id' => $receiptId,
        ];
    }

    /** @depends test_07_income_receipt_create_per_campaign */
    public function test_08a_income_discount_type1_paper1(): void
    {
        $this->_buildIncomeDiscount(1, 1, 0, 't1p1');
    }

    /** @depends test_07_income_receipt_create_per_campaign */
    public function test_08b_income_discount_type2_paper2(): void
    {
        $this->_buildIncomeDiscount(2, 2, 1, 't2p2');
    }

    /** @depends test_07_income_receipt_create_per_campaign */
    public function test_08c_income_discount_type3_paper3(): void
    {
        $this->_buildIncomeDiscount(3, 3, 2, 't3p3');
    }

    /** @depends test_07_income_receipt_create_per_campaign */
    public function test_08d_income_discount_type4_paper4(): void
    {
        $this->_buildIncomeDiscount(4, 4, 0, 't4p4'); // reuse campaign 1
    }

    // ─────────────────────────────────────────────────────────────
    // Step 9：作廢一張收入折讓（用 step 8a 建立的）
    // ─────────────────────────────────────────────────────────────
    /** @depends test_08a_income_discount_type1_paper1 */
    public function test_09_income_discount_void(): void
    {
        $result = Odoo::Create(
            OdooEndpoint::INCOME_DISCOUNT_VOID,
            new IncomeDiscountVoidDto(
                id: self::$ctx['income_discount_ids']['t1p1'],
                reason: 'Step 9 作廢',
                creator_name: 'LiveTest',
                creator_email: 'livetest@js-adways.com.tw',
                file: '',
            )
        );
        $this->assertOdooSuccess($result, 'INCOME_DISCOUNT_VOID (step 9)');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 10：收入發票列表 — 各種 filter 情境
    // ─────────────────────────────────────────────────────────────
    /** @depends test_07_income_receipt_create_per_campaign */
    public function test_10a_income_receipt_list_by_campaign(): void
    {
        [$c1, $c2, $c3] = self::$ctx['campaign_ids'];

        // 案例 1：campaign_id = [c1, c2] → 預期 2 筆
        $result1 = Odoo::List(
            OdooEndpoint::INCOME_RECEIPT_LIST,
            new IncomeReceiptListDto(
                filter: ['campaign_id' => [$c1, $c2]],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result1, 'INCOME_RECEIPT_LIST campaign_id=[c1,c2]');
        $this->assertCount(2, $result1['data'], '預期 2 筆 (campaign_id=[c1,c2])');

        // 案例 2：campaign_id = [] → 預期 3 筆（不限 campaign）
        $result2 = Odoo::List(
            OdooEndpoint::INCOME_RECEIPT_LIST,
            new IncomeReceiptListDto(
                filter: ['campaign_id' => []],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result2, 'INCOME_RECEIPT_LIST campaign_id=[]');
        $this->assertCount(3, $result2['data'], '預期 3 筆 (campaign_id=[])');
    }

    /** @depends test_07_income_receipt_create_per_campaign */
    public function test_10b_income_receipt_list_by_partner(): void
    {
        [$p1, $p2] = self::$ctx['partner_ids']; // 只取前兩個

        // 案例 1：partner_id = [p1, p2] → 預期 2 筆
        $result1 = Odoo::List(
            OdooEndpoint::INCOME_RECEIPT_LIST,
            new IncomeReceiptListDto(
                filter: ['partner_id' => [$p1, $p2]],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result1, 'INCOME_RECEIPT_LIST partner_id=[p1,p2]');
        $this->assertCount(2, $result1['data'], '預期 2 筆 (partner_id=[p1,p2])');

        // 案例 2：partner_id = [] → 預期 3 筆（不限 partner）
        $result2 = Odoo::List(
            OdooEndpoint::INCOME_RECEIPT_LIST,
            new IncomeReceiptListDto(
                filter: ['partner_id' => []],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result2, 'INCOME_RECEIPT_LIST partner_id=[]');
        $this->assertCount(3, $result2['data'], '預期 3 筆 (partner_id=[])');
    }

    public function test_10c_income_receipt_list_by_receipt_number(): void
    {
        // ─────────────────────────────────────────────────────────────
        // 因為剛建的草稿發票沒有發票號碼，需先到 Odoo 後台手動配號後，
        // 把真實號碼填到 self::$ctx['sample_income_receipt_number']（class 開頭那個 array）。
        // 支援字串（單筆）或陣列（多筆），例如：
        //   'sample_income_receipt_number' => 'AA12345678'
        //   'sample_income_receipt_number' => ['AA12345678', 'BB87654321']
        // ─────────────────────────────────────────────────────────────
        if (empty(self::$ctx['sample_income_receipt_number'])) {
            $this->markTestSkipped(
                "請先到 Odoo 後台取得真實發票號碼，並在 class 頂端的 \$ctx 設定 ".
                "'sample_income_receipt_number' => '你的號碼' 或 ['號碼1','號碼2']"
            );
        }

        $numbers = array_values((array) self::$ctx['sample_income_receipt_number']);

        $result = Odoo::List(
            OdooEndpoint::INCOME_RECEIPT_LIST,
            new IncomeReceiptListDto(
                filter: ['receipt_number' => $numbers],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'INCOME_RECEIPT_LIST by receipt_number');

        // 斷言 1：回傳筆數 == 填入的號碼數
        $this->assertCount(
            count($numbers),
            $result['data'],
            sprintf('預期 %d 筆 (填入 %d 個 receipt_number)', count($numbers), count($numbers))
        );

        // 斷言 2：每一筆回傳的 receipt_number 都必須在 filter 內
        foreach ($result['data'] as $i => $row) {
            $this->assertArrayHasKey('receipt_number', $row, "第 $i 筆缺 receipt_number 欄位");
            $this->assertContains(
                $row['receipt_number'],
                $numbers,
                "第 $i 筆的 receipt_number={$row['receipt_number']} 不在 filter 範圍內"
            );
        }
    }

    /** @depends test_07_income_receipt_create_per_campaign */
    public function test_10d_income_receipt_list_combined(): void
    {
        $c1 = self::$ctx['campaign_ids'][0];
        $p1 = self::$ctx['partner_ids'][0];
        $p2 = self::$ctx['partner_ids'][1];

        // 案例 1：c1 + p1（c1 屬於 p1）→ 預期 1 筆
        $result1 = Odoo::List(
            OdooEndpoint::INCOME_RECEIPT_LIST,
            new IncomeReceiptListDto(
                filter: ['campaign_id' => [$c1], 'partner_id' => [$p1]],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result1, 'INCOME_RECEIPT_LIST c1+p1');
        $this->assertCount(1, $result1['data'], '預期 1 筆 (c1 屬於 p1)');

        // 案例 2：c1 + p2（c1 不屬於 p2）→ 預期 0 筆
        $result2 = Odoo::List(
            OdooEndpoint::INCOME_RECEIPT_LIST,
            new IncomeReceiptListDto(
                filter: ['campaign_id' => [$c1], 'partner_id' => [$p2]],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result2, 'INCOME_RECEIPT_LIST c1+p2');
        $this->assertCount(0, $result2['data'], '預期 0 筆 (c1 不屬於 p2)');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 11：收入折讓列表 — 各種 filter 情境
    //
    // 所有 11x 都加上 primary_key=我們建立的 ids 作為 scope，
    // 避免 staging 累積資料（前次跑剩下的）污染計數。
    // ─────────────────────────────────────────────────────────────

    /** @depends test_08a_income_discount_type1_paper1 */
    public function test_11a_income_discount_list_by_primary_key(): void
    {
        $ourIds = array_values(self::$ctx['income_discount_ids']);

        $result = Odoo::List(
            OdooEndpoint::INCOME_DISCOUNT_LIST,
            new IncomeDiscountListDto(
                filter: ['primary_key' => $ourIds],
                order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'INCOME_DISCOUNT_LIST by primary_key');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($returnedIds);
        $expectedIds = $ourIds;
        sort($expectedIds);
        $this->assertEquals($expectedIds, $returnedIds, '回傳的 id 集合應與 filter primary_key 一致');
    }

    /** @depends test_08a_income_discount_type1_paper1 */
    public function test_11b_income_discount_list_by_partner(): void
    {
        $ourIds = array_values(self::$ctx['income_discount_ids']);
        $p1 = self::$ctx['partner_ids'][0];

        // 我們建立的 discount 中 partner_idx==0 的子集
        $expectedIds = [];
        foreach (self::$ctx['income_discount_meta'] ?? [] as $meta) {
            if ($meta['partner_idx'] === 0) {
                $expectedIds[] = $meta['id'];
            }
        }

        $result = Odoo::List(
            OdooEndpoint::INCOME_DISCOUNT_LIST,
            new IncomeDiscountListDto(
                filter: ['primary_key' => $ourIds, 'partner_id' => [$p1]],
                order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'INCOME_DISCOUNT_LIST by partner (scoped)');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($expectedIds);
        sort($returnedIds);
        $this->assertEquals(
            $expectedIds, $returnedIds,
            sprintf('預期 partner=p1 的 discount id 集合 (應為 %d 筆: %s)', count($expectedIds), json_encode($expectedIds))
        );
    }

    /** @depends test_08a_income_discount_type1_paper1 */
    public function test_11c_income_discount_list_by_discount_type(): void
    {
        $ourIds = array_values(self::$ctx['income_discount_ids']);

        foreach ([[1], [2], [3], [4], [1, 2, 3, 4]] as $types) {
            $expectedIds = [];
            foreach (self::$ctx['income_discount_meta'] ?? [] as $meta) {
                if (in_array($meta['discount_type'], $types, true)) {
                    $expectedIds[] = $meta['id'];
                }
            }

            $result = Odoo::List(
                OdooEndpoint::INCOME_DISCOUNT_LIST,
                new IncomeDiscountListDto(
                    filter: ['primary_key' => $ourIds, 'discount_type' => $types],
                    order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
                )
            );
            $label = 'discount_type='.json_encode($types);
            $this->assertOdooSuccess($result, "INCOME_DISCOUNT_LIST $label (scoped)");

            $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
            sort($expectedIds);
            sort($returnedIds);
            $this->assertEquals(
                $expectedIds, $returnedIds,
                sprintf('預期 %s 命中 %d 筆: %s', $label, count($expectedIds), json_encode($expectedIds))
            );
        }
    }

    /** @depends test_08a_income_discount_type1_paper1 */
    public function test_11d_income_discount_list_by_receipt_number(): void
    {
        if (empty(self::$ctx['sample_income_receipt_number'])) {
            $this->markTestSkipped(
                "請先在 class 頂端 \$ctx 設定 'sample_income_receipt_number' 後再執行"
            );
        }

        $ourIds = array_values(self::$ctx['income_discount_ids']);
        $numbers = array_values((array) self::$ctx['sample_income_receipt_number']);

        $result = Odoo::List(
            OdooEndpoint::INCOME_DISCOUNT_LIST,
            new IncomeDiscountListDto(
                filter: ['primary_key' => $ourIds, 'receipt_number' => $numbers],
                order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'INCOME_DISCOUNT_LIST by receipt_number (scoped)');

        // 內容：每筆回傳 discount 至少有一張 receipt 的 receipt_number 在 filter 內
        foreach ($result['data'] as $i => $discount) {
            $matched = false;
            foreach ($discount['receipt'] ?? [] as $r) {
                if (in_array($r['receipt_number'] ?? null, $numbers, true)) {
                    $matched = true;
                    break;
                }
            }
            $this->assertTrue(
                $matched,
                "第 $i 筆 discount(id={$discount['id']}) 沒有任何 receipt.receipt_number 在 filter 內"
            );
        }
    }

    /** @depends test_08a_income_discount_type1_paper1 */
    public function test_11e_income_discount_list_combined(): void
    {
        $ourIds = array_values(self::$ctx['income_discount_ids']);
        $p1 = self::$ctx['partner_ids'][0];
        $types = [1, 2, 3, 4];

        $filter = [
            'primary_key' => $ourIds,
            'partner_id' => [$p1],
            'discount_type' => $types,
        ];
        $hasReceiptNumber = !empty(self::$ctx['sample_income_receipt_number']);
        if ($hasReceiptNumber) {
            $filter['receipt_number'] = array_values((array) self::$ctx['sample_income_receipt_number']);
        }

        // 預期：在我們建的範圍內，partner_idx==0 AND discount_type ∈ [1..4]
        $expectedIds = [];
        foreach (self::$ctx['income_discount_meta'] ?? [] as $meta) {
            if ($meta['partner_idx'] === 0 && in_array($meta['discount_type'], $types, true)) {
                $expectedIds[] = $meta['id'];
            }
        }

        $result = Odoo::List(
            OdooEndpoint::INCOME_DISCOUNT_LIST,
            new IncomeDiscountListDto(
                filter: $filter,
                order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'INCOME_DISCOUNT_LIST combined');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);

        if (!$hasReceiptNumber) {
            sort($expectedIds);
            sort($returnedIds);
            $this->assertEquals(
                $expectedIds, $returnedIds,
                sprintf('預期 partner=p1 AND type∈[1..4] 集合: %s', json_encode($expectedIds))
            );
        } else {
            // 有 receipt_number filter 進一步過濾，回傳必為 expected 的子集
            foreach ($returnedIds as $rid) {
                $this->assertContains(
                    $rid, $expectedIds,
                    "回傳 id $rid 不在 (partner=p1 AND type∈[1..4]) 集合內"
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Step 12：作廢收入發票（用 receipt #1，其上的折讓已於 step 9 作廢）
    // ─────────────────────────────────────────────────────────────
    /** @depends test_09_income_discount_void */
    public function test_12_income_receipt_void(): void
    {
        $voidReceiptId = self::$ctx['receipt_ids'][0]; // 第 1 張：折讓已於 step 9 作廢
        $result = Odoo::Create(
            OdooEndpoint::INCOME_RECEIPT_VOID,
            new IncomeReceiptVoidDto(
                id: $voidReceiptId,
                reason: '開錯金額',
                creator_name: 'LiveTest',
                creator_email: 'livetest@js-adways.com.tw',
                reply_received: false,
                file: '',
                reply_file: '',
            )
        );
        $this->assertOdooSuccess($result, 'INCOME_RECEIPT_VOID');
        $this->assertArrayHasKey('id', $result['data']);

        self::$ctx['receipt_void_id'] = $result['data']['id'];
    }

    // ─────────────────────────────────────────────────────────────
    // Step 13：更新收入發票作廢資訊
    // ─────────────────────────────────────────────────────────────
    /** @depends test_12_income_receipt_void */
    public function test_13_income_receipt_void_update(): void
    {
        $result = Odoo::Update(
            OdooEndpoint::INCOME_RECEIPT_VOID_UPDATE,
            new IncomeReceiptVoidUpdateDto(
                id: self::$ctx['receipt_void_id'],
                reply_received: true,
                reply_file: '',
            )
        );
        $this->assertOdooSuccess($result, 'INCOME_RECEIPT_VOID_UPDATE');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 14：成本發票列表 — 從 staging 既有資料抓 seed 給 step 15 用
    // ─────────────────────────────────────────────────────────────
    public function test_14_setup_cost_receipt_seed(): void
    {
        $result = Odoo::List(
            OdooEndpoint::COST_RECEIPT_LIST,
            new CostReceiptListDto(
                filter: [],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_RECEIPT_LIST seed');
        $this->assertIsArray($result['data']);

        if (empty($result['data'])) {
            $this->markTestSkipped('Staging cost receipt list is empty — cannot seed 14x/15');
        }

        // 取前 10 筆 normalize 後當 seed
        $seed = array_map(fn ($row) => [
            'id' => $row['id'],
            'campaign_id' => $row['campaign_id'] ?? null,
            'partner_id' => $row['partner_id'] ?? null,
            'receipt_number' => $row['receipt_number'] ?? null,
            'cue' => array_map(fn ($c) => $c['id'], $row['cue'] ?? []),
        ], array_slice($result['data'], 0, 10));

        // 有 cue 的排到最前面（step 15 取 [0] 時優先拿到可用的）
        usort($seed, fn ($a, $b) => (empty($b['cue']) ? 0 : 1) - (empty($a['cue']) ? 0 : 1));

        self::$ctx['cost_receipt_seed'] = $seed;
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_14a_cost_receipt_list_by_primary_key(): void
    {
        $seedIds = array_map(fn ($s) => $s['id'], self::$ctx['cost_receipt_seed']);

        $result = Odoo::List(
            OdooEndpoint::COST_RECEIPT_LIST,
            new CostReceiptListDto(
                filter: ['primary_key' => $seedIds],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_RECEIPT_LIST by primary_key');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($seedIds);
        sort($returnedIds);
        $this->assertEquals($seedIds, $returnedIds, '回傳 id 集合應與 filter primary_key 一致');
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_14b_cost_receipt_list_by_campaign(): void
    {
        $seed = self::$ctx['cost_receipt_seed'];
        $seedIds = array_map(fn ($s) => $s['id'], $seed);
        $targetCampaignId = $seed[0]['campaign_id'];

        if (empty($targetCampaignId)) {
            $this->markTestSkipped('Seed[0] has no campaign_id');
        }

        // 預期：seed 中 campaign_id 等於 target 的子集
        $expectedIds = [];
        foreach ($seed as $s) {
            if ($s['campaign_id'] === $targetCampaignId) {
                $expectedIds[] = $s['id'];
            }
        }

        $result = Odoo::List(
            OdooEndpoint::COST_RECEIPT_LIST,
            new CostReceiptListDto(
                filter: ['primary_key' => $seedIds, 'campaign_id' => [$targetCampaignId]],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_RECEIPT_LIST by campaign (scoped)');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($expectedIds);
        sort($returnedIds);
        $this->assertEquals(
            $expectedIds, $returnedIds,
            sprintf('預期 campaign_id=%s 命中 %d 筆: %s', $targetCampaignId, count($expectedIds), json_encode($expectedIds))
        );
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_14c_cost_receipt_list_by_partner(): void
    {
        $seed = self::$ctx['cost_receipt_seed'];
        $seedIds = array_map(fn ($s) => $s['id'], $seed);
        $targetPartnerId = $seed[0]['partner_id'];

        if (empty($targetPartnerId)) {
            $this->markTestSkipped('Seed[0] has no partner_id');
        }

        $expectedIds = [];
        foreach ($seed as $s) {
            if ($s['partner_id'] === $targetPartnerId) {
                $expectedIds[] = $s['id'];
            }
        }

        $result = Odoo::List(
            OdooEndpoint::COST_RECEIPT_LIST,
            new CostReceiptListDto(
                filter: ['primary_key' => $seedIds, 'partner_id' => [$targetPartnerId]],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_RECEIPT_LIST by partner (scoped)');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($expectedIds);
        sort($returnedIds);
        $this->assertEquals(
            $expectedIds, $returnedIds,
            sprintf('預期 partner_id=%s 命中 %d 筆: %s', $targetPartnerId, count($expectedIds), json_encode($expectedIds))
        );
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_14d_cost_receipt_list_by_receipt_number(): void
    {
        $seed = self::$ctx['cost_receipt_seed'];
        $seedIds = array_map(fn ($s) => $s['id'], $seed);
        $targetRn = $seed[0]['receipt_number'];

        if (empty($targetRn)) {
            $this->markTestSkipped('Seed[0] has no receipt_number');
        }

        $expectedIds = [];
        foreach ($seed as $s) {
            if ($s['receipt_number'] === $targetRn) {
                $expectedIds[] = $s['id'];
            }
        }

        $result = Odoo::List(
            OdooEndpoint::COST_RECEIPT_LIST,
            new CostReceiptListDto(
                filter: ['primary_key' => $seedIds, 'receipt_number' => [$targetRn]],
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_RECEIPT_LIST by receipt_number (scoped)');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($expectedIds);
        sort($returnedIds);
        $this->assertEquals(
            $expectedIds, $returnedIds,
            sprintf('預期 receipt_number=%s 命中 %d 筆: %s', $targetRn, count($expectedIds), json_encode($expectedIds))
        );
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_14e_cost_receipt_list_combined(): void
    {
        $seed = self::$ctx['cost_receipt_seed'];
        $seedIds = array_map(fn ($s) => $s['id'], $seed);
        $target = $seed[0];

        $filter = ['primary_key' => $seedIds];
        if (!empty($target['campaign_id'])) $filter['campaign_id'] = [$target['campaign_id']];
        if (!empty($target['partner_id'])) $filter['partner_id'] = [$target['partner_id']];
        if (!empty($target['receipt_number'])) $filter['receipt_number'] = [$target['receipt_number']];

        // 預期：seed 中同時匹配所有非空 filter 的子集
        $expectedIds = [];
        foreach ($seed as $s) {
            if (!empty($target['campaign_id']) && $s['campaign_id'] !== $target['campaign_id']) continue;
            if (!empty($target['partner_id']) && $s['partner_id'] !== $target['partner_id']) continue;
            if (!empty($target['receipt_number']) && $s['receipt_number'] !== $target['receipt_number']) continue;
            $expectedIds[] = $s['id'];
        }

        $result = Odoo::List(
            OdooEndpoint::COST_RECEIPT_LIST,
            new CostReceiptListDto(
                filter: $filter,
                order_by: 'receipt_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_RECEIPT_LIST combined');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($expectedIds);
        sort($returnedIds);
        $this->assertEquals(
            $expectedIds, $returnedIds,
            sprintf('預期組合條件命中 %d 筆: %s', count($expectedIds), json_encode($expectedIds))
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Step 15：建立成本折讓 — 涵蓋 discount_type 1~4 × discount_paper_type 1~5
    // ─────────────────────────────────────────────────────────────
    private function _buildCostDiscount(int $discountType, int $paperType, string $label): void
    {
        $seed = self::$ctx['cost_receipt_seed'][0];
        if (empty($seed['cue'])) {
            //$this->markTestSkipped("Seed cost receipt has no cue ids — cannot build cost discount ($label)");
            $seed['cue'] = ['id'=>0];
        }

        $result = Odoo::Create(
            OdooEndpoint::COST_RECEIPT_DISCOUNT,
            new CostReceiptDiscountDto(
                discount_reason: '測試成本折讓 '.$label,
                discount_dt: '20260113',
                finance_dt: '20260113',
                discount_type: $discountType,
                discount_paper_type: $paperType,
                price: 5000,
                tax: 250,
                total_price: 5250,
                receipt: [
                    new CostDiscountReceiptDto(
                        id: $seed['id'],
                        discount_price: 5000,
                        cue: array_map(
                            fn ($cueId) => new CostDiscountReceiptCueDto(id: $cueId, discount_price: 2500),
                            array_slice($seed['cue'], 0, 2),
                        ),
                    ),
                ],
                creator_name: 'LiveTest',
                creator_email: 'livetest@js-adways.com.tw',
                allowance: new CostAllowanceDto(file: ''),
                credit_note: new CostCreditNoteDto(file: ''),
                debit_note: new CostDebitNoteDto(
                    dollar_type: 1, item_name: '折讓', debit_note_number: 'CDN'.$label,
                    date: '20260113', contact_name: '王小明',
                    contact_address: '台北市信義區忠孝東路260巷24號5樓', memo: '',
                ),
                income_receipt: new CostIncomeReceiptDto(
                    receipt_dt: '20260105', item_name: '退佣金',
                    receipt_number: 'CIR'.$label,
                    price: 1000, tax: 50, total_price: 1050,
                ),
            )
        );
        $this->assertOdooSuccess($result, "COST_RECEIPT_DISCOUNT $label");
        $this->assertArrayHasKey('id', $result['data']);

        self::$ctx['cost_discount_ids'] ??= [];
        self::$ctx['cost_discount_ids'][$label] = $result['data']['id'];
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_15a_cost_discount_type1_paper1(): void
    {
        $this->_buildCostDiscount(1, 1, 't1p1');
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_15b_cost_discount_type2_paper2(): void
    {
        $this->_buildCostDiscount(2, 2, 't2p2');
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_15c_cost_discount_type3_paper3(): void
    {
        $this->_buildCostDiscount(3, 3, 't3p3');
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_15d_cost_discount_type4_paper4(): void
    {
        $this->_buildCostDiscount(4, 4, 't4p4');
    }

    /** @depends test_14_setup_cost_receipt_seed */
    public function test_15e_cost_discount_type1_paper5(): void
    {
        $this->_buildCostDiscount(1, 5, 't1p5');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 16：作廢一張成本折讓（從 staging 既有資料抓 id；排除 step 18 預備使用的 t1p1）
    // ─────────────────────────────────────────────────────────────
    public function test_16_cost_discount_void(): void
    {
        $listResult = Odoo::List(
            OdooEndpoint::COST_DISCOUNT_LIST,
            new CostDiscountListDto(
                filter: [],
                order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($listResult, 'COST_DISCOUNT_LIST (seed for step 16)');

        if (empty($listResult['data'])) {
            $this->markTestSkipped('Staging cost discount list is empty — no real id to void');
        }

        // 排除 step 18 預備要 void 的 id（t1p1），避免兩個 step 撞 id 都失敗
        $protectedId = self::$ctx['cost_discount_ids']['t1p1'] ?? null;

        // 優先挑 status=1（草稿、未作廢）且不在保留清單內的；找不到就用第一筆非保留
        $targetId = null;
        $fallbackId = null;
        foreach ($listResult['data'] as $row) {
            if ($protectedId !== null && $row['id'] === $protectedId) continue;
            $fallbackId ??= $row['id'];
            if (($row['status'] ?? null) === 1) {
                $targetId = $row['id'];
                break;
            }
        }
        $targetId ??= $fallbackId;
        if ($targetId === null) {
            $this->markTestSkipped('找不到可作廢的成本折讓（排除 step 18 預備使用的 t1p1 後）');
        }

        $result = Odoo::Create(
            OdooEndpoint::COST_DISCOUNT_VOID,
            new CostDiscountVoidDto(
                id: $targetId,
                reason: 'Step 16 作廢',
                creator_name: 'LiveTest',
                creator_email: 'livetest@js-adways.com.tw',
                file: '',
            )
        );
        $this->assertOdooSuccess($result, 'COST_DISCOUNT_VOID (step 16)');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 17：成本折讓列表
    //
    // 先 17-setup 撈 staging 既有 cost discount 當 scope；
    // 17a-d 用 primary_key=seed_ids 鎖範圍，預期值從 seed metadata 動態算。
    // ─────────────────────────────────────────────────────────────
    public function test_17_setup_cost_discount_seed(): void
    {
        $result = Odoo::List(
            OdooEndpoint::COST_DISCOUNT_LIST,
            new CostDiscountListDto(
                filter: [],
                order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_DISCOUNT_LIST seed');

        if (empty($result['data'])) {
            self::$ctx['cost_discount_seed'] = [];
            $this->markTestSkipped('Staging cost discount list is empty — 17a-d 無法做精確 assertion');
        }

        // 前 10 筆 normalize：top-level id/type/paper_type + 內含每筆 receipt 的 partner_id 與 receipt_number
        $seed = [];
        foreach (array_slice($result['data'], 0, 10) as $row) {
            $seed[] = [
                'id' => $row['id'],
                'discount_type' => $row['discount_type'] ?? null,
                'discount_paper_type' => $row['discount_paper_type'] ?? null,
                'receipt_partner_ids' => array_values(array_filter(array_map(
                    fn ($r) => $r['partner_id'] ?? null,
                    $row['receipt'] ?? []
                ))),
                'receipt_numbers' => array_values(array_filter(array_map(
                    fn ($r) => $r['receipt_number'] ?? null,
                    $row['receipt'] ?? []
                ))),
            ];
        }
        self::$ctx['cost_discount_seed'] = $seed;
    }

    /** @depends test_17_setup_cost_discount_seed */
    public function test_17a_cost_discount_list_by_partner(): void
    {
        $seed = self::$ctx['cost_discount_seed'];
        $seedIds = array_map(fn ($s) => $s['id'], $seed);

        // 從 seed 找一個有 partner_id 可用的（拿第一個 seed[0] 的第一個 partner_id）
        $targetPartnerId = $seed[0]['receipt_partner_ids'][0] ?? null;
        if (empty($targetPartnerId)) {
            $this->markTestSkipped('Seed[0] discount 沒有任何 receipt.partner_id 可篩');
        }

        // 預期：seed 中至少有一筆 receipt.partner_id 等於 target 的 discount
        $expectedIds = [];
        foreach ($seed as $s) {
            if (in_array($targetPartnerId, $s['receipt_partner_ids'], true)) {
                $expectedIds[] = $s['id'];
            }
        }

        $result = Odoo::List(
            OdooEndpoint::COST_DISCOUNT_LIST,
            new CostDiscountListDto(
                filter: ['primary_key' => $seedIds, 'partner_id' => [$targetPartnerId]],
                order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_DISCOUNT_LIST by partner (scoped)');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($expectedIds);
        sort($returnedIds);
        $this->assertEquals(
            $expectedIds, $returnedIds,
            sprintf('預期 partner=%s 命中 %d 筆: %s', $targetPartnerId, count($expectedIds), json_encode($expectedIds))
        );
    }

    /** @depends test_17_setup_cost_discount_seed */
    public function test_17b_cost_discount_list_by_discount_type(): void
    {
        $seed = self::$ctx['cost_discount_seed'];
        $seedIds = array_map(fn ($s) => $s['id'], $seed);

        foreach ([[1], [2], [3], [4], [1, 2, 3, 4]] as $types) {
            $expectedIds = [];
            foreach ($seed as $s) {
                if (in_array($s['discount_type'], $types, true)) {
                    $expectedIds[] = $s['id'];
                }
            }

            $result = Odoo::List(
                OdooEndpoint::COST_DISCOUNT_LIST,
                new CostDiscountListDto(
                    filter: ['primary_key' => $seedIds, 'discount_type' => $types],
                    order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
                )
            );
            $label = 'discount_type='.json_encode($types);
            $this->assertOdooSuccess($result, "COST_DISCOUNT_LIST $label (scoped)");

            $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
            sort($expectedIds);
            sort($returnedIds);
            $this->assertEquals(
                $expectedIds, $returnedIds,
                sprintf('預期 %s 命中 %d 筆: %s', $label, count($expectedIds), json_encode($expectedIds))
            );
        }
    }

    /** @depends test_17_setup_cost_discount_seed */
    public function test_17c_cost_discount_list_by_receipt_number(): void
    {
        $seed = self::$ctx['cost_discount_seed'];
        $seedIds = array_map(fn ($s) => $s['id'], $seed);

        $targetRn = $seed[0]['receipt_numbers'][0] ?? null;
        if (empty($targetRn)) {
            $this->markTestSkipped('Seed[0] discount 沒有任何 receipt.receipt_number 可篩');
        }

        $expectedIds = [];
        foreach ($seed as $s) {
            if (in_array($targetRn, $s['receipt_numbers'], true)) {
                $expectedIds[] = $s['id'];
            }
        }

        $result = Odoo::List(
            OdooEndpoint::COST_DISCOUNT_LIST,
            new CostDiscountListDto(
                filter: ['primary_key' => $seedIds, 'receipt_number' => [$targetRn]],
                order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_DISCOUNT_LIST by receipt_number (scoped)');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($expectedIds);
        sort($returnedIds);
        $this->assertEquals(
            $expectedIds, $returnedIds,
            sprintf('預期 receipt_number=%s 命中 %d 筆: %s', $targetRn, count($expectedIds), json_encode($expectedIds))
        );
    }

    /** @depends test_17_setup_cost_discount_seed */
    public function test_17d_cost_discount_list_combined(): void
    {
        $seed = self::$ctx['cost_discount_seed'];
        $seedIds = array_map(fn ($s) => $s['id'], $seed);
        $target = $seed[0];

        $filter = ['primary_key' => $seedIds, 'discount_type' => [1, 2, 3, 4]];
        $targetPartnerId = $target['receipt_partner_ids'][0] ?? null;
        $targetRn = $target['receipt_numbers'][0] ?? null;
        if (!empty($targetPartnerId)) $filter['partner_id'] = [$targetPartnerId];
        if (!empty($targetRn)) $filter['receipt_number'] = [$targetRn];

        // 預期：seed 中 type∈[1..4] AND（如有指定）partner_id/receipt_number 都命中的 discount
        $expectedIds = [];
        foreach ($seed as $s) {
            if (!in_array($s['discount_type'], [1, 2, 3, 4], true)) continue;
            if (!empty($targetPartnerId) && !in_array($targetPartnerId, $s['receipt_partner_ids'], true)) continue;
            if (!empty($targetRn) && !in_array($targetRn, $s['receipt_numbers'], true)) continue;
            $expectedIds[] = $s['id'];
        }

        $result = Odoo::List(
            OdooEndpoint::COST_DISCOUNT_LIST,
            new CostDiscountListDto(
                filter: $filter,
                order_by: 'discount_date', order: 'DESC', page: 1, per_page: 30,
            )
        );
        $this->assertOdooSuccess($result, 'COST_DISCOUNT_LIST combined');

        $returnedIds = array_map(fn ($r) => $r['id'], $result['data']);
        sort($expectedIds);
        sort($returnedIds);
        $this->assertEquals(
            $expectedIds, $returnedIds,
            sprintf('預期組合條件命中 %d 筆: %s', count($expectedIds), json_encode($expectedIds))
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Step 18：作廢成本折讓（用 step 15a 建立的）
    // ─────────────────────────────────────────────────────────────
    /** @depends test_15a_cost_discount_type1_paper1 */
    public function test_18_cost_discount_void(): void
    {
        $result = Odoo::Create(
            OdooEndpoint::COST_DISCOUNT_VOID,
            new CostDiscountVoidDto(
                id: self::$ctx['cost_discount_ids']['t1p1'],
                reason: '廠商不收',
                creator_name: 'LiveTest',
                creator_email: 'livetest@js-adways.com.tw',
                file: '',
            )
        );
        $this->assertOdooSuccess($result, 'COST_DISCOUNT_VOID');
    }
}
