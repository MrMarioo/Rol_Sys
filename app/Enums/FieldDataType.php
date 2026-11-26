<?php

namespace App\Enums;

enum FieldDataType: string
{
    case SoilMoisture = 'soil_moisture';
    case Ndvi = 'ndvi';
    case Temperature = 'temperature';
    case PestDetection = 'pest_detection';
    case WeedDetection = 'weed_detection';
    case CropHealth = 'crop_health';
    case Other = 'other';
}
