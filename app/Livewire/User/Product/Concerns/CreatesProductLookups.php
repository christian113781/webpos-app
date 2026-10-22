<?php

namespace App\Livewire\User\Product\Concerns;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Support\Facades\Validator;

trait CreatesProductLookups
{
    public function createCategoryLookup(string $name): array
    {
        $result = $this->createLookup(Category::class, 'category', $name);
        if (isset($result['id'])) {
            $this->category_id = (string) $result['id'];
        }

        return $result;
    }

    public function createSupplierLookup(string $name, ?string $phone = null): array
    {
        $result = $this->createLookup(Supplier::class, 'supplier', $name, $phone);
        if (isset($result['id'])) {
            $this->supplier_id = (string) $result['id'];
        }

        return $result;
    }

    private function createLookup(string $modelClass, string $type, string $name, ?string $phone = null): array
    {
        $data = [
            'name' => trim($name),
            'phone' => filled($phone) ? trim($phone) : null,
        ];
        $rules = ['name' => ['required', 'string', 'max:255']];

        if ($type === 'supplier') {
            $rules['phone'] = ['nullable', 'string', 'max:50'];
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return ['errors' => $validator->errors()->toArray()];
        }

        $existing = $modelClass::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->first();

        if ($existing) {
            if ($existing->status !== 'active') {
                $kind = $type === 'category' ? 'category' : 'supplier';

                return [
                    'errors' => [
                        'name' => ["This {$kind} already exists but is inactive. Activate it before using it."],
                    ],
                ];
            }

            return [
                'id' => $existing->id,
                'name' => $existing->name,
                'subtitle' => $type === 'supplier'
                    ? collect([$existing->contact_person, $existing->phone])->filter()->implode(' · ')
                    : '',
            ];
        }

        $attributes = ['name' => $data['name'], 'status' => 'active'];
        if ($type === 'supplier') {
            $attributes['phone'] = $data['phone'];
        }

        $lookup = $modelClass::create($attributes);
        $this->dispatch('toast', message: ucfirst($type).' created and selected.');

        return [
            'id' => $lookup->id,
            'name' => $lookup->name,
            'subtitle' => $type === 'supplier' ? (string) $lookup->phone : '',
        ];
    }
}
