<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Component;

class DynamicInputs extends Component
{
    protected string $view = 'filament.forms.components.dynamic-inputs';

    protected array | Closure $items = [];

    public function items(array | Closure $items): static
    {
        $this->items = $items;

        return $this;
    }

    public static function make(): static
    {
        return new static();
    }

    protected function getFormSchema(): array
    {
        return [
            DescriptionList::make('overview')
                ->items([
                    'name' => $this->user->name,
                    'value' => $this->user->email,
                ]),
        ];
    }

    public function getItems(): array
    {
        return $this->evaluate($this->items);
    }
}