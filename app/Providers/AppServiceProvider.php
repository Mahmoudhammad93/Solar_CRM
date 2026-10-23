<?php

namespace App\Providers;

use App\Listeners\RecordUserLogin;
use App\Services\AllocationService;
use App\Services\AmcService;
use App\Services\AuditLogger;
use App\Services\BrandingService;
use App\Services\CalculationReportService;
use App\Services\Calculators\EvCalculatorService;
use App\Services\Calculators\HybridCalculatorService;
use App\Services\Calculators\InvestmentCalculatorService;
use App\Services\ConvertQuotationToProject;
use App\Services\DocumentService;
use App\Services\InvoiceReportService;
use App\Services\InvoiceService;
use App\Services\MessageService;
use App\Services\NumberingService;
use App\Services\ProjectMilestoneService;
use App\Services\QuotationReportService;
use App\Services\QuotationService;
use App\Services\QuotationTotals;
use App\Services\SettingsService;
use App\Services\StockService;
use App\Services\TicketReportService;
use App\Services\TicketService;
use App\Services\VisitReportService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(BrandingService::class);
        $this->app->singleton(NumberingService::class);
        $this->app->singleton(DocumentService::class);
        $this->app->singleton(VisitReportService::class);
        $this->app->singleton(StockService::class);
        $this->app->singleton(HybridCalculatorService::class);
        $this->app->singleton(InvestmentCalculatorService::class);
        $this->app->singleton(EvCalculatorService::class);
        $this->app->singleton(CalculationReportService::class);
        $this->app->singleton(QuotationTotals::class);
        $this->app->singleton(QuotationService::class);
        $this->app->singleton(QuotationReportService::class);
        $this->app->singleton(MessageService::class);
        $this->app->singleton(ConvertQuotationToProject::class);
        $this->app->singleton(ProjectMilestoneService::class);
        $this->app->singleton(AllocationService::class);
        $this->app->singleton(TicketService::class);
        $this->app->singleton(TicketReportService::class);
        $this->app->singleton(AmcService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(InvoiceReportService::class);
    }

    public function boot(): void
    {
        Event::listen(Login::class, RecordUserLogin::class);

        Password::defaults(function () {
            $min = 8;

            try {
                $min = (int) app(SettingsService::class)->get('security.password_min_length', 8);
            } catch (\Throwable) {
                // Settings table may not exist during early migrate
            }

            return Password::min(max(8, $min))->mixedCase()->numbers();
        });
    }
}
