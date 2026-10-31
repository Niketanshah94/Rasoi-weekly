import type { HouseholdForm } from '@/types/household';

export type DietMode = 'veg' | 'egg' | 'nonveg' | 'mixed';

/** Mirrors App\Enums\DietMode::for() (spec §3). */
export function dietMode(
    h: Pick<HouseholdForm, 'people' | 'nonveg_eaters' | 'veg_eat_eggs'>,
): DietMode {
    if (h.nonveg_eaters >= h.people) {
        return 'nonveg';
    }

    if (h.nonveg_eaters <= 0) {
        return h.veg_eat_eggs ? 'egg' : 'veg';
    }

    return 'mixed';
}

export const DIET_MODE_LABEL: Record<DietMode, string> = {
    veg: 'Veg',
    egg: 'Veg with eggs',
    nonveg: 'Non-veg',
    mixed: 'Mixed veg and non-veg',
};

/** The prototype's one-line explanation under the people steppers. */
export function dietModeSummary(
    h: Pick<HouseholdForm, 'people' | 'nonveg_eaters' | 'veg_eat_eggs'>,
): string {
    const veg = h.people - h.nonveg_eaters;

    switch (dietMode(h)) {
        case 'nonveg':
            return 'Everyone eats non-veg. Plans include chicken, mutton, fish and eggs.';
        case 'veg':
            return 'Pure veg plans, no eggs.';
        case 'egg':
            return 'Veg plans that include eggs.';
        default:
            return `${h.nonveg_eaters} non-veg, ${veg} veg${h.veg_eat_eggs ? ' (eggs are fine)' : ''}. Every non-veg meal comes with a veg dish, and the shopping list splits the portions.`;
    }
}

export const WHO_COOKS = [
    {
        value: 'self',
        name: 'We cook ourselves',
        note: 'Someone at home does the cooking',
    },
    {
        value: 'cook',
        name: 'A cook comes to help',
        note: 'We plan, our cook makes the food',
    },
    { value: 'both', name: 'Both', note: 'Some days us, some days our cook' },
] as const;
