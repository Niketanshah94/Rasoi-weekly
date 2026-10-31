export type LeaveOutOption = { value: string; label: string };

export type PlanOption = {
    key: string;
    name: string;
    description: string;
    /** Monday's breakfast, lunch and dinner. */
    monday: string[];
};

export type CountryOption = {
    code: string;
    name: string;
    dialCode: string;
    mobilePlaceholder: string;
    mobileLength: number;
    leaveOuts: LeaveOutOption[];
    plans: PlanOption[];
};

export type HouseholdForm = {
    country: string;
    who_cooks: 'self' | 'cook' | 'both';
    people: number;
    nonveg_eaters: number;
    veg_eat_eggs: boolean;
    leave_out: string[];
    plan: string | null;
    cook_name: string;
    cook_phone: string;
    cook_language: 'hi' | 'en';
};
