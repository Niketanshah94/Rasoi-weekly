<?php

namespace App\Http\Controllers;

use App\Enums\BillingInterval;
use App\Enums\PriceMarket;
use App\Models\Country;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Terms, privacy and refunds. These are the prototype's draft outlines: lawyers write the
 * final text for each country before launch (spec §11).
 */
class LegalController extends Controller
{
    public const PAGES = ['terms', 'privacy', 'refund'];

    public function show(Request $request, string $page): Response
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        $country = $request->user('web')?->household()->country
            ?? Country::where('code', strtoupper((string) $request->query('country', Country::INDIA)))->first()
            ?? Country::where('code', Country::INDIA)->firstOrFail();

        return Inertia::render('legal/Show', [
            'page' => $page,
            'pages' => [
                ['key' => 'terms', 'short' => 'Terms'],
                ['key' => 'privacy', 'short' => 'Privacy'],
                ['key' => 'refund', 'short' => 'Refunds'],
            ],
            ...$this->content($page, $country),
        ]);
    }

    /**
     * @return array{title: string, sections: list<array{heading: string, text?: string, items?: list<string>}>}
     */
    private function content(string $page, Country $country): array
    {
        return match ($page) {
            'terms' => ['title' => 'Terms of service', 'sections' => [
                ['heading' => 'The service', 'text' => 'Rasoi plans household meals, builds shopping lists and gives recipes for personal, non-commercial use.'],
                ['heading' => 'Your account', 'text' => 'You sign in with Google or with a link we email you. Keep your email account secure; you’re responsible for activity on your account.'],
                ['heading' => 'Free trial and subscription', 'items' => [
                    'One 7-day free trial per person and per payment method.',
                    'Starting a trial sets up a recurring payment mandate. Nothing is charged during the trial.',
                    $this->priceText($country),
                ]],
                ['heading' => 'Nutrition and allergens', 'text' => 'Nutrition values are estimates. Allergen filters rely on our ingredient data; always check packaged ingredients. Rasoi is not medical or dietary advice.'],
                ['heading' => 'Changes and contact', 'text' => 'We’ll tell you before changing these terms. Questions: [support email and address].'],
            ]],
            'privacy' => ['title' => 'Privacy policy', 'sections' => [
                ['heading' => 'What we collect', 'items' => [
                    'Your name and email address',
                    'Your WhatsApp number, only if you add it for updates',
                    'Household details: people, food preferences, things you leave out',
                    'Your cook’s name and WhatsApp number, if you add them',
                    'How you use the app, to improve it',
                ]],
                ['heading' => 'Why', 'text' => 'To plan your meals, send reminders and menus, and process payments.'],
                ['heading' => 'Who we share it with', 'text' => 'Only service providers that run Rasoi: the payment gateway, SMS and WhatsApp providers, and hosting. We never sell your data.'],
                ['heading' => 'Your rights', 'text' => "Under {$country->privacy_law}, you can access, correct or delete your data and withdraw consent. Delete your account anytime from Account."],
                ['heading' => 'Grievance officer', 'text' => '[Name, email and address]'],
            ]],
            default => ['title' => 'Refunds and cancellation', 'sections' => [
                ['heading' => 'Cancelling', 'text' => 'Cancel anytime from Account, or revoke the mandate in your payment app or with your bank. Cancel during the trial and you’re never charged.'],
                ['heading' => 'After a charge', 'text' => 'You keep full access until the end of the period you paid for. Renewals stop after you cancel.'],
                ['heading' => 'Refunds', 'text' => '[Define the policy, for example: duplicate or wrongly taken charges refunded within 7 working days.]'],
                ['heading' => 'How to ask', 'text' => 'Write to [support email] or use Contact support in Account, with your registered mobile number.'],
            ]],
        };
    }

    private function priceText(Country $country): string
    {
        $india = $country->code === Country::INDIA;
        $market = $india ? PriceMarket::India : PriceMarket::International;
        $format = fn (BillingInterval $interval) => $india
            ? '₹'.Number::format(Price::for($market, $interval)->amount_minor / 100, locale: 'en_IN')
            : ($country->code === 'US' ? '$' : 'US$').number_format(Price::for($market, $interval)->amount_minor / 100, 2);

        return "After the trial: {$format(BillingInterval::Monthly)} a month or {$format(BillingInterval::Yearly)} a year until you cancel. {$country->tax_note}";
    }
}
