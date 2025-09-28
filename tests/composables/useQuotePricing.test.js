// src/shared/composables/useQuotePricing.js

const toArray = (v) => {
  if (v == null) return []
  if (Array.isArray(v)) return v
  if (typeof v === 'number' || typeof v === 'string') return [v]
  if (typeof v === 'object') return Object.keys(v) // {id: qty} -> ['id', ...]
  return []
}

const num = (v, d = 0) => {
  const n = Number(v)
  return Number.isFinite(n) ? n : d
}

// diff de nuits entre start (inclus) et endExclusive (exclu)
const computeNights = (startStr, endExclusiveStr) => {
  const s = new Date(startStr)
  const e = new Date(endExclusiveStr)
  if (!Number.isFinite(+s) || !Number.isFinite(+e)) return 0
  const ms = +e - +s
  if (ms <= 0) return 0
  return Math.floor(ms / (1000 * 60 * 60 * 24))
}

/**
 * API attendue par tes tests:
 * computeQuoteTotal({
 *   selected: { activities:Array|Object|..., room:Number|Array|Object, menus:Array|Object },
 *   catalog:  { activities:[], room:[], menus:[] },
 *   dates:    { start, endExclusive, guests },
 *   overrides:{ activity:Object, menu:Object, room:Object },
 *   config?:  { roomPerNight?: boolean } // facultatif
 * })
 */
export function computeQuoteTotal(input = {}) {
  const selected = input.selected ?? {}
  const catalog  = input.catalog ?? {}
  const dates    = input.dates ?? {}
  const overrides = input.overrides ?? {}
  const cfg      = input.config ?? {}

  const nights = computeNights(dates.start, dates.endExclusive)
  const guests = num(dates.guests, 0)

  const catActivities = Array.isArray(catalog.activities) ? catalog.activities : []
  const catRooms      = Array.isArray(catalog.room) ? catalog.room : (Array.isArray(catalog.rooms) ? catalog.rooms : [])
  const catMenus      = Array.isArray(catalog.menus) ? catalog.menus : []

  const selActivities = selected.activities
  const selRooms      = selected.room
  const selMenus      = selected.menus

  const ovAct  = overrides.activity || {}
  const ovMenu = overrides.menu || {}
  const ovRoom = overrides.room || {}

  const lines = []
  let total = 0

  // ROOMS — qty = (overrideRooms || 1) * (cfg.roomPerNight ? nights : nights)
  // => dans tes tests c’est bien "par nuit", donc nights obligatoire
  for (const id of toArray(selRooms)) {
    const prod = catRooms.find(r => String(r.id) === String(id))
    if (!prod) continue
    const roomsCount = num(ovRoom[String(id)], 1) // override = nb de chambres
    const nightsCount = Math.max(0, nights)
    const qty = roomsCount * nightsCount
    if (qty <= 0) continue
    const unitPrice = num(prod.price, 0)
    const lineTotal = unitPrice * qty
    total += lineTotal
    lines.push({
      type: 'room',
      id: prod.id,
      name: prod.name,
      qty,
      unitPrice,
      lineTotal
    })
  }

  // ACTIVITIES — qty par défaut = 1 ; override.activity[id] = qty absolue
  for (const id of toArray(selActivities)) {
    const prod = catActivities.find(a => String(a.id) === String(id))
    if (!prod) continue
    const qty = num(ovAct[String(id)], 1)
    if (qty <= 0) continue
    const unitPrice = num(prod.price, 0)
    const lineTotal = unitPrice * qty
    total += lineTotal
    lines.push({
      type: 'activity',
      id: prod.id,
      name: prod.name,
      qty,
      unitPrice,
      lineTotal
    })
  }

  // MENUS — qty par défaut = 1 * guests * nights ; override.menu[id] = perGuestPerNight factor
  for (const id of toArray(selMenus)) {
    const prod = catMenus.find(m => String(m.id) === String(id))
    if (!prod) continue
    const factor = num(ovMenu[String(id)], 1) // "par guest par nuit"
    const qty = factor * Math.max(0, guests) * Math.max(0, nights)
    if (qty <= 0) continue
    const unitPrice = num(prod.price, 0)
    const lineTotal = unitPrice * qty
    total += lineTotal
    lines.push({
      type: 'menu',
      id: prod.id,
      name: prod.name,
      qty,
      unitPrice,
      lineTotal
    })
  }

  return { nights, total, lines }
}
