<?php

namespace App\Http\Controllers;

use App\Dto\Select2AjaxDto;
use App\Models\Crop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CropController extends Controller
{
    public function find(): JsonResponse
    {
        return response()->json(
            Select2AjaxDto::fromQuery(Crop::querySearch(), 'select2label'),
        );
    }
}
