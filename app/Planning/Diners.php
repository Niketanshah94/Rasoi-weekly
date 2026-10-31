<?php

namespace App\Planning;

use App\Enums\Allergen;
use App\Enums\DietMode;
use App\Enums\LeaveOut;
use App\Models\Household;

/**
 * Who a week is planned for: the household's country, people, diets and leave-outs.
 */
final readonly class Diners
{
    /**
     * @param  list<LeaveOut>  $leaveOuts
     */
    public function __construct(
        public string $country,
        public int $people,
        public int $nonVegEaters,
        public bool $vegEatEggs,
        public array $leaveOuts,
    ) {}

    /**
     * @param  list<string>  $leaveOuts
     */
    public static function fromKeys(string $country, int $people, int $nonVegEaters, bool $vegEatEggs, array $leaveOuts): self
    {
        return new self($country, $people, $nonVegEaters, $vegEatEggs, array_map(LeaveOut::from(...), $leaveOuts));
    }

    public static function fromHousehold(Household $household): self
    {
        return new self(
            $household->country->code,
            $household->people,
            $household->nonveg_eaters,
            $household->veg_eat_eggs,
            array_values($household->leave_out->all()),
        );
    }

    public function dietMode(): DietMode
    {
        return DietMode::for($this->people, $this->nonVegEaters, $this->vegEatEggs);
    }

    /**
     * The diet the veg eaters in a mixed home follow.
     */
    public function vegSideMode(): DietMode
    {
        return $this->vegEatEggs ? DietMode::Egg : DietMode::Veg;
    }

    public function vegEaters(): int
    {
        return $this->people - $this->nonVegEaters;
    }

    /**
     * @return list<Allergen>
     */
    public function excludedAllergens(): array
    {
        $allergens = [];
        foreach ($this->leaveOuts as $leaveOut) {
            foreach ($leaveOut->allergens() as $allergen) {
                $allergens[$allergen->value] = $allergen;
            }
        }

        return array_values($allergens);
    }
}
