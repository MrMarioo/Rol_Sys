export const isNumber = (value) => value !== null && value !== undefined && !Number.isNaN(Number(value));

export const formatNdvi = (value) => (isNumber(value) ? Number(value).toFixed(2) : '—');

export const formatPercent = (value) => (isNumber(value) ? `${Number(value).toFixed(1)}%` : '—');

export const formatPercentRounded = (value) => (isNumber(value) ? `${Math.round(Number(value))}%` : '—');

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

export const formatDays = (days) => `${days} ${Number(days) === 1 ? 'day' : 'days'}`;
