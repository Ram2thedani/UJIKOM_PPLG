export function formatDuration(totalSeconds) {
  const secs = Math.max(0, Number(totalSeconds) || 0)
  const mins = Math.floor(secs / 60)
  const rem = secs % 60
  return `${String(mins).padStart(2, '0')}:${String(rem).padStart(2, '0')}`
}

export function formatScore(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return '-'
  }
  return Number(value).toFixed(2)
}

export function formatDateTime(isoString) {
  if (!isoString) return '-'
  const date = new Date(isoString)
  if (Number.isNaN(date.getTime())) return isoString
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date)
}
