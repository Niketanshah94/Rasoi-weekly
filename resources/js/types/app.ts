/** Props shared by every customer tab (WeekPresenter::shell). */
export type Shell = {
    weekOf: string;
    initial: string;
    today: number;
    days: { short: string; full: string; date: number }[];
    cookTabLabel: string;
    hasAccess: boolean;
};

export type DishCard = {
    key: string;
    name: string;
    nameHi: string | null;
    diet: 'veg' | 'egg' | 'nonveg';
    mood: 'healthy' | 'comfort' | 'treat';
    moodLabel: string;
    cuisine: string;
    cuisineCode: string;
    minutes: number;
    kcal: number;
    protein: number;
    fibre: number;
    spice: number;
    meals: string[];
};

export type Recipe = DishCard & {
    prep: number;
    cook: number;
    effort: number;
    carbs: number;
    fat: number;
    allergens: string[];
    avoidsLeaveOuts: boolean;
    servings: number;
    ingredients: { name: string; quantity: string }[];
    steps: string[];
};

export type HouseholdSummary = {
    summary: string;
    leaveOutSummary: string;
    dietMode: 'veg' | 'egg' | 'nonveg' | 'mixed';
    isMixed: boolean;
    people: number;
    vegEaters: number;
    whoCooks: 'self' | 'cook' | 'both';
    cookName: string | null;
};

export type HealthCheckRow = {
    label: string;
    value: string;
    target: string;
    state: 'ok' | 'low' | 'high';
    stateLabel: string;
};

export type PlanMeal = {
    meal: string;
    label: string;
    time: string;
    dish: DishCard;
    pair: DishCard | null;
    portions: number;
    swapped: boolean;
    cooked: boolean;
};

export type PlanDay = {
    kcal: number;
    meals: PlanMeal[];
    status: 'healthy' | 'nearly' | 'unbalanced';
    statusLabel: string;
    met: number;
    checks: HealthCheckRow[];
    praise: string;
    fix: {
        meal: string;
        mealLabel: string;
        replace: string;
        with: string;
        withName: string;
        met: number;
    } | null;
};

export const MEALS = [
    { value: 'breakfast', label: 'Breakfast' },
    { value: 'lunch', label: 'Lunch' },
    { value: 'evening_snack', label: 'Evening snack' },
    { value: 'dinner', label: 'Dinner' },
    { value: 'late_night', label: 'Late-night craving' },
] as const;

export const STATUS_COLOR = {
    healthy: 'var(--leaf)',
    nearly: 'var(--turmeric)',
    unbalanced: 'var(--chili)',
} as const;
export const MOOD_COLOR = {
    healthy: 'var(--leaf)',
    comfort: 'var(--turmeric)',
    treat: 'var(--chili)',
} as const;

export const plural = (n: number, one: string, many: string): string =>
    n === 1 ? one : many;
export const timeLabel = (minutes: number): string =>
    minutes <= 2 ? 'No cooking' : `${minutes} min`;
