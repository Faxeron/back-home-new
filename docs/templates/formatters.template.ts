export const formatDateShort = (value?: unknown) => {
  if (!value) return '\u2014'
  const raw = String(value)
  const datePart = raw.split('T')[0]
  const parts = datePart.split('-')
  if (parts.length === 3 && parts[0].length === 4) {
    const [year, month, day] = parts
    if (year && month && day) return `${day}.${month}.${year}`
  }
  const date = value instanceof Date ? value : new Date(String(value))
  if (Number.isNaN(date.getTime())) return '\u2014'
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  return `${day}.${month}.${date.getFullYear()}`
}

export const formatSum = (sum: unknown) => {
  const amountValue = typeof sum === 'object' ? (sum as any)?.amount ?? sum : sum
  const amount = Number(amountValue)
  if (!Number.isFinite(amount)) return (amountValue as any) ?? ''
  return amount.toLocaleString('en-US')
}
