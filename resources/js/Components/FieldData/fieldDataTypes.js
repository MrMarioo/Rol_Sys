export const DATA_TYPE_OPTIONS = [
    { value: 'soil_moisture', label: 'Soil Moisture' },
    { value: 'ndvi', label: 'Vegetation Index (NDVI)' },
    { value: 'temperature', label: 'Temperature' },
    { value: 'pest_detection', label: 'Pest Detection' },
    { value: 'weed_detection', label: 'Weed Detection' },
    { value: 'crop_health', label: 'Crop Health' },
    { value: 'other', label: 'Other' },
];

const FORMAT_DESCRIPTIONS = {
    soil_moisture: 'Format: Time series with moisture readings',
    ndvi: 'Format: NDVI map or time series',
    temperature: 'Format: Time series with temperature readings',
    pest_detection: 'Format: Detected pests with locations',
    weed_detection: 'Format: Detected weeds with locations',
    crop_health: 'Format: Crop health index',
};

export const dataFormatDescription = (type) => FORMAT_DESCRIPTIONS[type] ?? 'Format: JSON data';

const HOUR = 3600000;

const hoursAgo = (hours) => new Date(Date.now() - hours * HOUR).toISOString();

const SAMPLES = {
    soil_moisture: () => ({
        readings: [
            { timestamp: hoursAgo(0), value: 35.2, depth: 10 },
            { timestamp: hoursAgo(1), value: 34.8, depth: 10 },
            { timestamp: hoursAgo(2), value: 34.5, depth: 10 },
        ],
        unit: 'percent',
        average: 34.83,
    }),
    ndvi: () => ({
        grid: [
            [0.65, 0.67, 0.71, 0.69],
            [0.62, 0.63, 0.64, 0.66],
            [0.6, 0.61, 0.63, 0.62],
            [0.59, 0.58, 0.61, 0.6],
        ],
        min: 0.58,
        max: 0.71,
        average: 0.63,
    }),
    temperature: () => ({
        readings: [
            { timestamp: hoursAgo(0), temperature: 22.5 },
            { timestamp: hoursAgo(1), temperature: 21.8 },
            { timestamp: hoursAgo(2), temperature: 21.2 },
        ],
        unit: 'celsius',
        average: 21.83,
    }),
    pest_detection: () => ({
        detections: [
            { type: 'aphid', count: 15, severity: 'medium', location: [51.123, 19.456] },
            { type: 'beetle', count: 3, severity: 'low', location: [51.124, 19.457] },
        ],
        summary: {
            total_count: 18,
            highest_severity: 'medium',
        },
    }),
    weed_detection: () => ({
        detections: [
            { type: 'thistle', coverage: 0.05, location: [51.123, 19.456] },
            { type: 'nettle', coverage: 0.03, location: [51.124, 19.457] },
        ],
        total_coverage: 0.08,
    }),
    crop_health: () => ({
        health_index: 0.82,
        areas: [
            { location: [51.123, 19.456], index: 0.78, issue: 'chlorosis' },
            { location: [51.124, 19.457], index: 0.85, issue: null },
        ],
    }),
};

export const sampleDataJson = (type) => {
    const sample = SAMPLES[type]?.() ?? { value: 'Sample data', timestamp: hoursAgo(0) };

    return JSON.stringify(sample, null, 4);
};

export const parseJson = (text) => {
    try {
        return { valid: true, data: JSON.parse(text) };
    } catch {
        return { valid: false, data: null };
    }
};
