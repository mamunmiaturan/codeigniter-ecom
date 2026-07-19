<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DatabaseSeeder extends Seeder {
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
        echo "Starting Database Seeding..." . PHP_EOL;

        // 1. Core tables
        $this->call('RolesSeeder');
        $this->call('UserSeeder');
        $this->call('PermissionModulesSeeder');
        $this->call('PermissionSeeder');
        $this->call('UserPrivilegesSeeder');

        // 2. Settings & Config
        $this->call('GlobalSettingsSeeder');
        $this->call('ThemeSettingsSeeder');
        $this->call('EmailConfigSeeder');
        $this->call('EmailTemplatesSeeder');
        $this->call('SmsConfigSeeder');
        $this->call('SmsTemplatesSeeder');

        // 3. Language settings
        $this->call('LanguagesSeeder');
        $this->call('LanguageListSeeder');

        // 4. Auth related
        $this->call('LoginCredentialSeeder');
        $this->call('LoginAttemptsSeeder');
        $this->call('ResetPasswordSeeder');
        $this->call('OtpHistorySeeder');

        // 5. Logs & Jobs
        $this->call('JobsSeeder');
        $this->call('ActivityLogsSeeder');
        $this->call('SmsLogsSeeder');

        // 6. E-Commerce catalog (idempotent: schema + permissions + sample data)
        $this->call('CatalogSeeder');

        // 7. Storefront customers + cart (idempotent: schema + Customer role)
        $this->call('CustomerCartSeeder');

        // 8. Orders / OMS (idempotent: schema + order permission)
        $this->call('OrderSeeder');

        // 9. Coupons (idempotent: schema + coupon permission + samples)
        $this->call('CouponSeeder');

        // 10. Product reviews (idempotent: schema + review permission + samples)
        $this->call('ReviewSeeder');

        // 11. Customer wishlist (idempotent: schema only)
        $this->call('WishlistSeeder');

        // 12. Checkout backbone — shipping, tax, payment (idempotent)
        $this->call('ShippingSeeder');
        $this->call('TaxSeeder');
        $this->call('PaymentSeeder');

        // 13. Promotions — cart price rules + catalog price rules (idempotent)
        $this->call('PromotionSeeder');

        // 14. Sales ops — invoices, shipments, refunds, returns/RMA (idempotent)
        $this->call('SalesOpsSeeder');

        // 15. CMS pages (idempotent: schema + permission + sample pages)
        $this->call('CmsSeeder');

        // 15b. Content modules — FAQ, Contact inbox, Blog (idempotent)
        $this->call('FaqSeeder');
        $this->call('ContactSeeder');
        $this->call('BlogSeeder');

        // 15c. Banners (idempotent) + product-catalog enhancement columns
        $this->call('BannerSeeder');
        $this->call('ProductEnhancementsSeeder');

        // 15c-ii. Women & Kids demo catalog — categories, brands, 100+ products
        // with real info + downloaded imagery (idempotent). Runs after the
        // tags/label columns exist.
        $this->call('WomenChildCatalogSeeder');

        // 15e. Flash Sale (idempotent: schema + permission + sample sale/items)
        $this->call('FlashSaleSeeder');

        // 15d. Reports & Analytics (permission + sidebar only, read-only)
        $this->call('ReportSeeder');

        // 16. Marketing (newsletter) + customer groups (idempotent)
        $this->call('MarketingSeeder');
        $this->call('CustomerGroupSeeder');

        // 17. Product types — virtual + downloadable (idempotent)
        $this->call('ProductTypeSeeder');

        // 18. EAV attributes — attributes/families/groups/options (idempotent)
        $this->call('EavSeeder');

        // 19. Composite product types — configurable/grouped/bundle (idempotent)
        $this->call('CompositeProductSeeder');

        // 20. Multi-source inventory — sources + per-source stock (idempotent)
        $this->call('InventorySeeder');

        // 20b. Stock movement ledger (schema only; reuses inventory_source perms)
        $this->call('StockMovementsSeeder');

        // 20c. Customer email-verification columns (idempotent)
        $this->call('EmailVerificationSeeder');

        // 20d. RMA exchange type column (idempotent)
        $this->call('RmaTypeSeeder');

        // Runs last: regroups the storefront/content permissions (banner, blog,
        // cms, faq, flash_sale, newsletter) under a single "Website" module to
        // mirror the sidebar's Website menu. Must follow all six content seeders.
        $this->call('WebsiteModuleSeeder');

        // Groups Coupons under the Promotions module to mirror its sidebar menu.
        // Must follow CouponSeeder + PromotionSeeder.
        $this->call('PromotionsModuleSeeder');

        // Groups Contact Messages under a "Support" module. Must follow ContactSeeder.
        $this->call('SupportModuleSeeder');

        // Customer support desk: complaints + tickets (+ their permissions).
        $this->call('SupportDeskSeeder');

        echo "Database Seeding Completed Successfully!" . PHP_EOL;
    }
}
