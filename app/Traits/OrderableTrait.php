<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait OrderableTrait
{
    protected function getOrderable(): array
    {
        return [];
    }

    public function scopeQueryOrder(Builder $query): void
    {
        $query->when(request()->has('order_by'), function ($query) {
            $orderBy = request()->input('order_by');
            $orderable = array_merge(
                ['id', 'created_at', 'updated_at'],
                $this->getOrderable(),
            );

            if (in_array($orderBy, $orderable)) {
                $query->orderBy(
                    $orderBy,
                    request()->input('order_dir') ?? 'asc',
                );
            }
        });

        if (request()->input('order_by') != 'id') {
            $query->orderBy('id', 'desc');
        }
    }
}
