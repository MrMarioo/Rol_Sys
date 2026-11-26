const EMPTY = '—';

export const isNumber = (value) => value !== null && value !== undefined && value !== '' && !Number.isNaN(Number(value));

export const formatNdvi = (value) => (isNumber(value) ? Number(value).toFixed(2) : EMPTY);

export const formatPercent = (value) => (isNumber(value) ? `${Number(value).toFixed(1)}%` : EMPTY);

export const formatPercentRounded = (value) => (isNumber(value) ? `${Math.round(Number(value))}%` : EMPTY);

export const formatMoisture = (value) => (isNumber(value) ? formatPercent(Number(value) * 100) : EMPTY);

export const formatConfidence = (value) => (isNumber(value) ? `${Math.round(Number(value) * 100)}%` : EMPTY);

export const formatDays = (days) => `${days} ${Number(days) === 1 ? 'day' : 'days'}`;

export const formatNumber = (value, decimals = 2) =>
    isNumber(value)
        ? Number(value).toLocaleString('en-US', {
              minimumFractionDigits: decimals,
              maximumFractionDigits: decimals,
          })
        : EMPTY;

const parseDate = (value) => {
    if (!value) return null;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
};

export const formatDate = (value) => {
    const date = parseDate(value);
    return date ? date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : EMPTY;
};

export const formatDateTime = (value) => {
    const date = parseDate(value);
    return date
        ? date.toLocaleString('en-US', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : EMPTY;
};

export const formatLabel = (value) => {
    if (!value) return EMPTY;
    return String(value)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const DATA_TYPE_LABELS = {
    ndvi: 'NDVI',
};

export const formatDataType = (type) => DATA_TYPE_LABELS[type] ?? formatLabel(type);

export const formatLocation = (latitude, longitude) =>
    isNumber(latitude) && isNumber(longitude) ? `${Number(latitude).toFixed(5)}, ${Number(longitude).toFixed(5)}` : EMPTY;

export const statusClass = (status) => {
    if (status === 'Good' || status === 'Optimal') return 'bg-success';
    if (status === 'Moderate') return 'bg-info';
    if (status === 'Poor' || status === 'Low' || status === 'High') return 'bg-warning';
    if (status === 'Critical') return 'bg-danger';
    return 'bg-light text-dark';
};

export const priorityClass = (priority) => {
    if (priority === 'high') return 'bg-danger';
    if (priority === 'medium') return 'bg-warning';
    if (priority === 'low') return 'bg-success';
    return 'bg-light text-dark';
};

const RECOMMENDATION_CLASSES = {
    urgent: 'bg-danger',
    high: 'bg-warning',
    medium: 'bg-info',
    scheduled: 'bg-primary',
    predictive: 'bg-info',
    positive: 'bg-success',
};

export const recommendationClass = (type) => RECOMMENDATION_CLASSES[type] ?? 'bg-success';
