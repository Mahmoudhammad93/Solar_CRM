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
use App\Services\DashboardMetrics;
use App\Services\DocumentService;
use App\Services\InvoiceReportService;
use App\Services\InvoiceService;
use App\Services\MessageService;
use App\Services\MessageTemplateService;
use App\Services\NumberingService;
use App\Services\ProjectMilestoneService;
use App\Services\QuotationReportService;
use App\Services\QuotationService;
use App\Services\QuotationTotals;
use App\Services\ReportExportService;
use App\Services\SettingsService;
use App\Services\StockService;
use App\Services\TicketReportService;
use App\Services\TicketService;
use App\Services\VisitReportService;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->singleton(MessageTemplateService::class);
        $this->app->singleton(MessageService::class);
        $this->app->singleton(ConvertQuotationToProject::class);
        $this->app->singleton(ProjectMilestoneService::class);
        $this->app->singleton(AllocationService::class);
        $this->app->singleton(TicketService::class);
        $this->app->singleton(TicketReportService::class);
        $this->app->singleton(AmcService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(InvoiceReportService::class);
        $this->app->singleton(DashboardMetrics::class);
        $this->app->singleton(ReportExportService::class);
    }

    public function boot(): void
    {
        Event::listen(Login::class, RecordUserLogin::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        // Public quote verification — slow brute-force of public_token.
        RateLimiter::for('quote-verify', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('document-download', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

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
