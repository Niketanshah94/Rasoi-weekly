<?php

namespace App\Filament\Pages;

use App\Enums\BillingInterval;
use App\Enums\PriceMarket;
use App\Filament\Support\AdminActivity;
use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\Price;
use App\Models\Staff;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use UnitEnum;

/**
 * India's rupee prices, the two USD prices and the exchange rates used to show local amounts.
 *
 * @property-read Schema $form
 */
class Pricing extends Page
{
    /** Currencies shown at the daily rate; INR is only used to report revenue in rupees. */
    public const RATE_CURRENCIES = ['GBP', 'AED', 'SGD', 'INR'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected ?string $subheading = 'India is billed in rupees through Razorpay. Everywhere else uses two Polar products in US dollars, shown in local currency.';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $rates = ExchangeRate::latest();
        $price = fn (PriceMarket $market, BillingInterval $interval) => Price::for($market, $interval)->amount_minor / 100;

        $this->form->fill([
            'inr_monthly' => $price(PriceMarket::India, BillingInterval::Monthly),
            'inr_yearly' => $price(PriceMarket::India, BillingInterval::Yearly),
            'usd_monthly' => $price(PriceMarket::International, BillingInterval::Monthly),
            'usd_yearly' => $price(PriceMarket::International, BillingInterval::Yearly),
            'rates' => collect(self::RATE_CURRENCIES)->mapWithKeys(fn (string $c) => [$c => $rates->get($c)?->rate])->all(),
            'rates_date' => $rates->max('as_of')?->toDateString() ?? now()->toDateString(),
        ]);
    }

    public static function canEdit(): bool
    {
        $staff = auth('staff')->user();

        return $staff instanceof Staff && $staff->role->canEditPricing();
    }

    public function form(Schema $schema): Schema
    {
        $amount = fn (string $name, string $label, string $step) => TextInput::make($name)
            ->label($label)->numeric()->step($step)->minValue($step === '1' ? 1 : 0.01)->required()->live(onBlur: true);

        return $schema
            ->statePath('data')
            ->disabled(! self::canEdit())
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->schema([
                    Section::make('India, Razorpay')->schema([
                        $amount('inr_monthly', 'Monthly (₹)', '1')->integer(),
                        $amount('inr_yearly', 'Yearly (₹)', '1')->integer(),
                        Html::make(fn (Get $get) => e(sprintf('Yearly saves %d%% and works out to ₹%s a month.',
                            Price::savingPercent(self::minor($get('inr_monthly')), self::minor($get('inr_yearly'))),
                            Number::format((float) $get('inr_yearly') / 12, 0, locale: 'en_IN')))),
                    ]),
                    Section::make('Everywhere else, Polar')->schema([
                        $amount('usd_monthly', 'Monthly (US$)', '0.01'),
                        $amount('usd_yearly', 'Yearly (US$)', '0.01'),
                        Html::make(fn (Get $get) => e(sprintf('Yearly saves %d%%.',
                            Price::savingPercent(self::minor($get('usd_monthly')), self::minor($get('usd_yearly')))))),
                        Callout::make('Update Polar and the app stores to match')
                            ->description('These prices change what the app shows. Update the Monthly and Yearly products in Polar, and the in-app products in the App Store and Google Play, or people will be charged the old amount.')
                            ->warning(),
                    ]),
                    Section::make('Exchange rates, per US$1')->schema([
                        ...array_map(fn (string $c) => TextInput::make("rates.{$c}")->label($c)->numeric()->step('any')->minValue(0.00000001)->required()->live(onBlur: true), self::RATE_CURRENCIES),
                        DatePicker::make('rates_date')->label('Rate date')->required()->native(false)->maxDate(now()),
                    ]),
                ]),
                Section::make('What customers see')->schema([
                    Html::make(fn (Get $get) => self::previewTable($get)),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save prices')->submit('save')->visible(self::canEdit()),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(self::canEdit(), 403);

        $data = $this->form->getState();
        $before = $this->snapshot();

        DB::transaction(function () use ($data) {
            $prices = [
                [PriceMarket::India, BillingInterval::Monthly, 'inr_monthly'],
                [PriceMarket::India, BillingInterval::Yearly, 'inr_yearly'],
                [PriceMarket::International, BillingInterval::Monthly, 'usd_monthly'],
                [PriceMarket::International, BillingInterval::Yearly, 'usd_yearly'],
            ];
            foreach ($prices as [$market, $interval, $field]) {
                Price::for($market, $interval)->update(['amount_minor' => self::minor($data[$field])]);
            }

            foreach (self::RATE_CURRENCIES as $currency) {
                ExchangeRate::updateOrCreate(
                    ['base' => 'USD', 'currency' => $currency, 'as_of' => $data['rates_date']],
                    ['rate' => $data['rates'][$currency]],
                );
            }
        });

        AdminActivity::record(
            sprintf('Updated prices: India ₹%s a month and ₹%s a year; elsewhere US$%s a month and US$%s a year. Exchange rates dated %s.',
                Number::format((float) $data['inr_monthly'], locale: 'en_IN'), Number::format((float) $data['inr_yearly'], locale: 'en_IN'),
                number_format((float) $data['usd_monthly'], 2), number_format((float) $data['usd_yearly'], 2), $data['rates_date']),
            properties: ['before' => $before, 'after' => $this->snapshot()],
        );

        Notification::make()->title('Prices saved. Update the Polar and store products to match.')->success()->send();
    }

    /**
     * Major units as typed (149, 5.99) to integer minor units (14900, 599).
     */
    private static function minor(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(): array
    {
        return [
            'prices' => Price::all()->mapWithKeys(fn (Price $p) => ["{$p->market->value}_{$p->interval->value}" => $p->amount_minor])->all(),
            'rates' => ExchangeRate::latest()->map(fn (ExchangeRate $r) => $r->rate)->all(),
        ];
    }

    private static function previewTable(Get $get): string
    {
        $rows = Country::orderBy('position')->get()->map(function (Country $country) use ($get) {
            if ($country->code === Country::INDIA) {
                return [$country->name, '₹'.Number::format((float) $get('inr_monthly'), locale: 'en_IN'), '₹'.Number::format((float) $get('inr_yearly'), locale: 'en_IN'), 'Razorpay, rupees'];
            }

            $rate = $country->currency === 'USD' ? 1.0 : (float) $get("rates.{$country->currency}");
            $local = fn (string $field) => (string) Number::currency((float) $get($field) * $rate, in: $country->currency, locale: 'en');

            return [$country->name, $local('usd_monthly'), $local('usd_yearly'), $country->currency === 'USD' ? 'Polar, US dollars' : 'Polar, charged in US dollars'];
        });

        $cell = fn (string $tag, string $text, string $attrs = '') => "<{$tag} class=\"px-3 py-2 text-start\"{$attrs}>".e($text)."</{$tag}>";

        return '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr>'
            .$cell('th', 'Country', ' scope="col"').$cell('th', 'Monthly', ' scope="col"').$cell('th', 'Yearly', ' scope="col"').$cell('th', 'Billing', ' scope="col"')
            .'</tr></thead><tbody>'
            .$rows->map(fn (array $r) => '<tr class="border-t border-gray-200 dark:border-white/10">'.$cell('th', $r[0], ' scope="row"').$cell('td', $r[1]).$cell('td', $r[2]).$cell('td', $r[3]).'</tr>')->join('')
            .'</tbody></table></div>';
    }
}
