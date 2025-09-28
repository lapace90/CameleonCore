export function computeNights(checkin, checkout) {
  if (!checkin || !checkout) return 0
  const inDate  = new Date(`${checkin}T00:00:00`)
  const outDate = new Date(`${checkout}T00:00:00`)
  const diff = (outDate - inDate) / (1000 * 60 * 60 * 24)
  return Math.max(0, Math.floor(diff))
}

/**
 * overrides: { activity: { [id]: qty }, menu: { [id]: qty } }
 */
export function computeQuoteTotal({ selected, catalog, dates, rules = {}, overrides = {} }) {
  const nights = computeNights(dates?.checkin, dates?.checkout)
  const guests = Math.max(1, Number(dates?.guests ?? 1)) 

  const cfg = {
    roomPerNight: true,
    // menus/activités : règles par défaut
    menuPerGuest: true,               
    menuPerGuestPerNight: false,
    activityPerGuest: true,
    activityPerGuestPerNight: false,
    // garde-fous
    capQtyToGuests: true,
    minQty: 0,
    ...rules
  }

  const num = v => Number(v ?? 0) || 0
  const clamp = (v, min, max) => Math.max(min, isFinite(max) ? Math.min(v, max) : v)
  const getOv = (type, id) => {
    const raw = overrides?.[type]?.[id]
    return Number.isFinite(Number(raw)) ? Number(raw) : null
  }

  let total = 0
  const lines = []

  // ROOM = prix * nuits (pas d'override manuel)
  for (const id of selected.room || []) {
    const p = (catalog.rooms || []).find(r => r.id === id); if (!p) continue
    let qty = cfg.roomPerNight ? Math.max(1, nights) : 1
    const lineTotal = num(p.price) * qty
    total += lineTotal
    lines.push({ type:'room', id, name:p.name, unit:num(p.price), qty, lineTotal })
  }

  // ACTIVITIES (par personne / (option) par nuit) + override
  for (const id of selected.activity || []) {
    const p = (catalog.activities || []).find(a => a.id === id); if (!p) continue
    let qty = 1
    if (cfg.activityPerGuest) qty *= Math.max(1, guests)
    if (cfg.activityPerGuestPerNight) qty *= Math.max(1, nights)

    const ov = getOv('activity', id)
    if (ov !== null) qty = ov

    if (cfg.capQtyToGuests) qty = clamp(qty, cfg.minQty, guests)
    qty = Math.floor(qty)

    const lineTotal = num(p.price) * qty
    total += lineTotal
    lines.push({ type:'activity', id, name:p.name, unit:num(p.price), qty, lineTotal })
  }

  // MENUS (par personne, pas par nuit) + override
  for (const id of selected.menu || []) {
    const p = (catalog.menus || []).find(m => m.id === id); if (!p) continue
    let qty = cfg.menuPerGuest ? Math.max(1, guests) : 1

    const ov = getOv('menu', id)
    if (ov !== null) qty = ov

    if (cfg.capQtyToGuests) qty = clamp(qty, cfg.minQty, guests)
    qty = Math.floor(qty)

    const lineTotal = num(p.price) * qty
    total += lineTotal
    lines.push({ type:'menu', id, name:p.name, unit:num(p.price), qty, lineTotal })
  }

  return { total, nights, lines }
}
