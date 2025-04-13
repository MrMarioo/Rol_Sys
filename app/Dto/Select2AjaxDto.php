<?php

namespace App\Dto;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;

class Select2AjaxDto
{
    public string $label;
    public string $value;
    public bool $disabled;
    public array $model;

    public function __construct(
        $model,
        string $label = 'label',
        string $value = 'id',
        bool $withModel = false,
        Closure|null $disabled = null,
    ) {
        $this->label = $model->$label;
        $this->value = $model->$value;
        $this->disabled = $disabled ? $disabled($model) : false;
        $this->model = $withModel ? $model->toArray() : [];
    }

    public static function fromQuery(
        $query,
        string $label = 'label',
        string $value = 'id',
        string $ajaxIdField = 'id',
        bool $withModel = false,
        Closure|null $disabled = null,
    ): LengthAwarePaginator {
        $originalQuery = clone $query;
        $initialResults = collect([]);
        $paginate = config('pagination.ajax');

        // get the initial results
        if ($ajax_id = request()->input('ajax_id')) {
            $initialResults = $query->where($ajaxIdField, $ajax_id)->get();

            // remove the initial results from the query
            $originalQuery->where($ajaxIdField, '!=', $ajax_id);
            $paginate -= $initialResults->count();
        }

        $paginator = $originalQuery->paginate($paginate);
        $collection = $paginator->getCollection();
        // add the initial results at the beginning of the collection
        $collection = $initialResults->merge($collection);
        $paginator->setCollection($collection);

        if (request()->strict && $ajax_id) {
            $paginator->setCollection($initialResults);
        }

        return $paginator->through(function ($item) use (
            $label,
            $value,
            $withModel,
            $disabled,
        ) {
            return new self($item, $label, $value, $withModel, $disabled);
        });
    }
}
