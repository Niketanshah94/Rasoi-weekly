/**
 * The launch country to pre-select on sign-up, from the device's time zone (spec §1).
 */
const ZONES: Record<string, string> = {
    'Asia/Kolkata': 'IN',
    'Asia/Calcutta': 'IN',
    'Europe/London': 'GB',
    'Asia/Dubai': 'AE',
    'Asia/Singapore': 'SG',
};

export function countryFromTimeZone(available: string[]): string {
    let zone = '';

    try {
        zone = Intl.DateTimeFormat().resolvedOptions().timeZone ?? '';
    } catch {
        zone = '';
    }

    const guess =
        ZONES[zone] ??
        (zone.startsWith('America/') || zone.startsWith('US/') ? 'US' : 'IN');

    return available.includes(guess) ? guess : (available[0] ?? 'IN');
}
