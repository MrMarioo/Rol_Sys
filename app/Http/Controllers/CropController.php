<?php

namespace App\Http\Controllers;

use App\Dto\Select2AjaxDto;
use App\Models\Crop;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CropController extends Controller
{
    public function find(): JsonResponse
    {
        return response()->json(
            data: Select2AjaxDto::fromQuery(Crop::querySearch(), 'select2label'),
            status: HttpResponse::HTTP_OK,
        );
    }
}