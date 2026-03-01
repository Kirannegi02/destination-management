import { useEffect, useMemo, useState } from 'react'

// Base URL for your live Laravel API.
// For your server this should be:
//   https://esmo-2025.org/api
// You can override it via VITE_API_BASE_URL in transport-frontend/.env.
const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || 'https://esmo-2025.org/api'

const emptyForm = {
  tripType: 'multicity',
  passengers: 40,
  cities: ['Paris', 'Zurich'],
  daysPerCity: [3, 3],
  travelModeBetweenCities: ['coach'], // 'train' | 'coach' per leg
  remarks: '',
  guestName: '',
  guestEmail: '',
  guestPhone: '',
  guestCountry: '',
}

function App() {
  const [vehicles, setVehicles] = useState([])
  const [vehiclesLoading, setVehiclesLoading] = useState(false)
  const [vehiclesError, setVehiclesError] = useState(null)

  const [form, setForm] = useState(emptyForm)
  const [submitting, setSubmitting] = useState(false)
  const [quote, setQuote] = useState(null)
  const [quoteError, setQuoteError] = useState(null)
  const [showAdvanced, setShowAdvanced] = useState(false)

  // Load fleet dynamically from admin data
  useEffect(() => {
    const fetchVehicles = async () => {
      try {
        setVehiclesLoading(true)
        setVehiclesError(null)
        const res = await fetch(`${API_BASE_URL}/vehicles?per_page=100`)
        if (!res.ok) {
          throw new Error('Failed to load vehicles')
        }
        const json = await res.json()
        if (json.success) {
          setVehicles(json.data || [])
        } else {
          throw new Error(json.message || 'Failed to load vehicles')
        }
      } catch (err) {
        setVehiclesError(err.message)
      } finally {
        setVehiclesLoading(false)
      }
    }

    fetchVehicles()
  }, [])

  const groupedFleet = useMemo(() => {
    const groups = {}
    for (const v of vehicles) {
      const key = v.category_label || v.vehicle_category || 'Other'
      if (!groups[key]) groups[key] = []
      groups[key].push(v)
    }
    return groups
  }, [vehicles])

  const suggestedVehicleText = useMemo(() => {
    const pax = Number(form.passengers || 0)
    if (!pax) return ''
    if (pax <= 7) return 'System will usually select a VAN or similar small vehicle.'
    if (pax <= 19) return 'System will usually select a 16 / 19 seater minibus.'
    return 'System will usually select a Full Size Coach (e.g. 49 / 55 / 59 seater).'
  }, [form.passengers])

  const handleTripTypeChange = (tripType) => {
    setForm((prev) => {
      let cities = [...prev.cities]
      if (tripType === 'return' && cities.length >= 1) {
        const start = cities[0] || ''
        const end = cities[cities.length - 1] || start
        if (start && end && start !== end) {
          cities = [start, start]
        }
      }
      if (tripType === 'one_way') {
        cities = cities.slice(0, 2)
      }
      return {
        ...prev,
        tripType,
        cities,
        travelModeBetweenCities: Array(Math.max(1, cities.length - 1)).fill('coach'),
      }
    })
  }

  const handleCityChange = (index, value) => {
    setForm((prev) => {
      const cities = [...prev.cities]
      cities[index] = value
      return { ...prev, cities }
    })
  }

  const handleDaysChange = (index, value) => {
    const num = value === '' ? '' : Math.max(0, Number(value))
    setForm((prev) => {
      const days = [...prev.daysPerCity]
      days[index] = num
      return { ...prev, daysPerCity: days }
    })
  }

  const handlePassengersChange = (value) => {
    const num = value === '' ? '' : Math.max(1, Number(value))
    setForm((prev) => ({ ...prev, passengers: num }))
  }

  const handleLegModeChange = (index, mode) => {
    setForm((prev) => {
      const modes = [...prev.travelModeBetweenCities]
      modes[index] = mode
      return { ...prev, travelModeBetweenCities: modes }
    })
  }

  const addCity = () => {
    setForm((prev) => {
      const cities = [...prev.cities, '']
      const daysPerCity = [...prev.daysPerCity, 0]
      const travelModeBetweenCities = [
        ...prev.travelModeBetweenCities,
        'coach',
      ]
      return { ...prev, cities, daysPerCity, travelModeBetweenCities }
    })
  }

  const removeCity = (index) => {
    setForm((prev) => {
      if (prev.cities.length <= 2) return prev
      const cities = prev.cities.filter((_, i) => i !== index)
      const daysPerCity = prev.daysPerCity.filter((_, i) => i !== index)
      const travelModeBetweenCities = prev.travelModeBetweenCities.slice(
        0,
        Math.max(1, cities.length - 1),
      )
      return { ...prev, cities, daysPerCity, travelModeBetweenCities }
    })
  }

  const hasReturnCityMismatch =
    form.tripType === 'return' &&
    form.cities[0] &&
    form.cities[form.cities.length - 1] &&
    form.cities[0] !== form.cities[form.cities.length - 1]

  const buildQuotePayload = () => {
    const cities = form.cities.map((c) => c.trim()).filter(Boolean)
    const days_per_city = form.daysPerCity.map((d) =>
      d === '' ? 0 : Number(d || 0),
    )
    const legs_by_train = form.travelModeBetweenCities.map(
      (mode) => mode === 'train',
    )

    return {
      trip_type: form.tripType,
      passengers: Number(form.passengers || 0),
      cities,
      days_per_city,
      legs_by_train,
      legs: [],
    }
  }

  const handleGetQuote = async () => {
    setQuote(null)
    setQuoteError(null)

    const payload = buildQuotePayload()
    if (!payload.passengers || payload.cities.length < 2) {
      setQuoteError('Please enter passengers and at least start and end cities.')
      return
    }

    if (form.tripType === 'return' && hasReturnCityMismatch) {
      setQuoteError(
        'For Return trip, start and end city should be the same. Use Multi City if they are different.',
      )
      return
    }

    try {
      setSubmitting(true)
      const res = await fetch(`${API_BASE_URL}/transports/quote`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      })
      const json = await res.json()
      if (!res.ok || !json.success) {
        throw new Error(json.message || 'Could not build quote')
      }
      setQuote({
        vehicle: json.data?.vehicle ?? null,
        lineItems: json.data?.line_items ?? [],
        totalAmount: json.data?.total_amount ?? null,
        currency: json.data?.currency ?? '',
      })
    } catch (err) {
      setQuoteError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  const handleSubmitRequest = async () => {
    setQuoteError(null)

    const payload = {
      ...buildQuotePayload(),
      remarks: form.remarks || null,
      guest_name: form.guestName || null,
      guest_email: form.guestEmail || null,
      guest_phone: form.guestPhone || null,
      guest_country: form.guestCountry || null,
    }

    try {
      setSubmitting(true)
      const res = await fetch(`${API_BASE_URL}/transports/quote-request`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      })
      const json = await res.json()
      if (!res.ok || !json.success) {
        throw new Error(json.message || 'Could not submit quote request')
      }
      const breakdown = json.data?.quote_breakdown ?? {}
      setQuote({
        vehicle: breakdown.vehicle ?? null,
        lineItems: breakdown.line_items ?? [],
        totalAmount: breakdown.total_amount ?? null,
        currency: breakdown.currency ?? '',
      })
    } catch (err) {
      setQuoteError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  const handleDownloadQuote = (currentQuote) => {
    if (!currentQuote || !currentQuote.lineItems || currentQuote.lineItems.length === 0) {
      return
    }

    const title = 'Transport Quotation'
    const w = window.open('', '_blank', 'width=900,height=700')
    if (!w) return

    const rowsHtml = currentQuote.lineItems
      .map(
        (item) => `
        <tr>
          <td>${item.day ?? ''}</td>
          <td>${item.city ?? ''}</td>
          <td>${item.description ?? ''}</td>
          <td>${item.vehicle_display ?? ''}</td>
          <td style="text-align:right; white-space:nowrap;">
            ${item.amount != null ? `${item.currency || currentQuote.currency || ''} ${item.amount}` : '-'}
          </td>
        </tr>`,
      )
      .join('')

    const totalHtml =
      currentQuote.totalAmount != null
        ? `<div style="margin-top:12px; font-size:13px; display:flex; justify-content:space-between;">
             <span><strong>Total</strong></span>
             <span><strong>${currentQuote.currency || ''} ${currentQuote.totalAmount}</strong></span>
           </div>`
        : ''

    const vehicleLine = currentQuote.vehicle
      ? `${currentQuote.vehicle.name || ''}${
          currentQuote.vehicle.capacity_seats
            ? ` • ${currentQuote.vehicle.capacity_seats} Pax`
            : ''
        }`
      : ''

    w.document.write(`<!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8" />
          <title>${title}</title>
          <style>
            body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 24px; color: #0f172a; }
            h1 { font-size: 20px; margin-bottom: 4px; }
            h2 { font-size: 14px; margin: 0 0 12px; color: #4b5563; }
            table { width: 100%; border-collapse: collapse; font-size: 11px; }
            th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
            thead { background: #f3f4f6; }
            .amount { text-align: right; white-space: nowrap; }
            .meta { font-size: 12px; margin-bottom: 12px; color: #4b5563; }
            @media print {
              body { margin: 8mm 10mm; }
            }
          </style>
        </head>
        <body>
          <h1>${title}</h1>
          <div class="meta">${vehicleLine}</div>
          <table>
            <thead>
              <tr>
                <th>Day</th>
                <th>City / Route</th>
                <th>Description</th>
                <th>Vehicle</th>
                <th class="amount">Amount</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml}
            </tbody>
          </table>
          ${totalHtml}
        </body>
      </html>`)
    w.document.close()
    w.focus()
    w.print()
  }

  return (
    <div className="transport-app">
      {/* Top nav */}
      <header className="transport-nav">
        <div className="transport-nav-inner">
          <div className="transport-brand">
            <div className="transport-logo">T</div>
            <div>
              <p className="transport-brand-title">DMS Transport</p>
              <p className="transport-brand-subtitle">
                Dispatch &amp; Mobility Suite
              </p>
            </div>
          </div>
          <nav className="transport-nav-links">
            <button className="nav-link">Dashboard</button>
            <button className="nav-link">Bookings</button>
            <button className="nav-link">Vehicles</button>
            <button className="nav-link">Drivers</button>
          </nav>
          <button
            className="primary-pill-button"
            type="button"
            onClick={handleSubmitRequest}
            disabled={submitting}
          >
            {submitting ? 'Submitting…' : 'Get Quote &amp; Book Live'}
          </button>
        </div>
      </header>

      {/* Hero + content */}
      <main className="transport-main">
        <section className="transport-hero-layout">
          {/* Left: intro + form */}
          <div className="transport-hero-left">
            <div className="hero-pill">
              <span className="hero-pill-dot" />
              <span>Live Transport Quotes</span>
              <span className="hero-pill-separator">•</span>
              <span>Coach &amp; Van Hire</span>
            </div>

            <h1 className="hero-title">
              Simple, blue <span>coach hire</span> experience.
            </h1>
            <p className="hero-subtitle">
              Enter your journey and passengers in a single screen. We’ll match the
              right vehicle from your fleet and prepare a downloadable quotation
              instantly.
            </p>

            <div className="booking-card">
              <div className="booking-card-header">
                <span className="booking-card-header-title">Journey details</span>
                <span className="booking-card-header-step">Step 1 of 2</span>
              </div>
              <div className="booking-card-body">
              {/* Column 1: Trip type */}
              <div className="form-section">
                <div className="form-section-header">
                  <h3>Trip type</h3>
                  <p>One Way / Return / Multi City</p>
                </div>
                <div className="trip-type-row">
                  <button
                    type="button"
                    className={
                      form.tripType === 'one_way'
                        ? 'chip chip-active'
                        : 'chip'
                    }
                    onClick={() => handleTripTypeChange('one_way')}
                  >
                    One Way
                  </button>
                  <button
                    type="button"
                    className={
                      form.tripType === 'return' ? 'chip chip-active' : 'chip'
                    }
                    onClick={() => handleTripTypeChange('return')}
                  >
                    Return
                  </button>
                  <button
                    type="button"
                    className={
                      form.tripType === 'multicity'
                        ? 'chip chip-active'
                        : 'chip'
                    }
                    onClick={() => handleTripTypeChange('multicity')}
                  >
                    Multi City
                  </button>
                </div>
              </div>

              {/* Column 2: Passengers */}
              <div className="form-section">
                <div className="form-section-header">
                  <h3>Passengers</h3>
                  <p>We auto pick coach size</p>
                </div>
                <div className="booking-row">
                  <div className="booking-field passengers">
                    <label>Number of passengers</label>
                    <input
                      type="number"
                      min="1"
                      value={form.passengers}
                      onChange={(e) => handlePassengersChange(e.target.value)}
                    />
                  </div>
                  {suggestedVehicleText && (
                    <p className="field-helper">{suggestedVehicleText}</p>
                  )}
                </div>
              </div>

              {/* Column 3: Destinations (quick quote) */}
              <div className="form-section">
                <div className="form-section-header">
                  <h3>Destinations</h3>
                  <p>Start and end are enough for a quick quote</p>
                </div>
                <div className="cities-grid">
                  {form.cities.map((city, index) => (
                    <div key={index} className="city-row">
                      <div className="booking-field">
                        <label>
                          {index === 0
                            ? 'Start city'
                            : index === form.cities.length - 1
                              ? 'End city'
                              : `City ${index + 1}`}
                        </label>
                        <input
                          type="text"
                          value={city}
                          onChange={(e) =>
                            handleCityChange(index, e.target.value)
                          }
                          placeholder={
                            index === 0
                              ? 'e.g. Paris'
                              : index === form.cities.length - 1
                                ? 'e.g. Zurich'
                                : 'e.g. Lucerne'
                          }
                        />
                      </div>
                      {form.tripType === 'multicity' &&
                        form.cities.length > 2 &&
                        index > 0 &&
                        index < form.cities.length - 1 && (
                          <button
                            type="button"
                            className="remove-city-btn"
                            onClick={() => removeCity(index)}
                          >
                            Remove
                          </button>
                        )}
                    </div>
                  ))}
                </div>
                {form.tripType === 'multicity' && (
                  <button
                    type="button"
                    className="link-button"
                    onClick={addCity}
                  >
                    + Add another city
                  </button>
                )}
                {hasReturnCityMismatch && (
                  <p className="error-text">
                    For Return selection, start and end city should be the same.
                    Otherwise choose Multi City.
                  </p>
                )}
              </div>

              {/* Toggle for advanced routing options */}
              <button
                type="button"
                className="toggle-advanced-btn"
                onClick={() => setShowAdvanced((v) => !v)}
              >
                {showAdvanced ? 'Hide advanced routing options' : 'Show advanced routing options'}
              </button>

              {/* Column 4: Days per city (advanced but optional) */}
              {showAdvanced && (
                <div className="form-section">
                  <div className="form-section-header">
                    <h3>Stay pattern</h3>
                    <p>No. of days in each city</p>
                  </div>
                  <div className="cities-grid">
                    {form.cities.map((city, index) => (
                      <div key={index} className="city-row">
                        <div className="booking-field small">
                          <label>
                            {city || `City ${index + 1}`} – Days
                          </label>
                          <input
                            type="number"
                            min="0"
                            value={
                              form.daysPerCity[index] === ''
                                ? ''
                                : form.daysPerCity[index] ?? 0
                            }
                            onChange={(e) =>
                              handleDaysChange(index, e.target.value)
                            }
                          />
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Column 5: Mode between cities (Train vs Coach) */}
              {showAdvanced && form.cities.length > 1 && (
                <div className="form-section">
                  <div className="form-section-header">
                    <h3>Travel between cities</h3>
                    <p>Train / own arrangement vs coach</p>
                  </div>
                  <div className="legs-grid">
                    {form.cities.slice(0, -1).map((city, index) => {
                      const nextCity = form.cities[index + 1]
                      const mode = form.travelModeBetweenCities[index] || 'coach'
                      return (
                        <div key={index} className="leg-row">
                          <div className="leg-label">
                            {city || 'City'} → {nextCity || 'City'}
                          </div>
                          <div className="leg-modes">
                            <button
                              type="button"
                              className={
                                mode === 'train'
                                  ? 'chip chip-active'
                                  : 'chip'
                              }
                              onClick={() =>
                                handleLegModeChange(index, 'train')
                              }
                            >
                              By Train / Other
                            </button>
                            <button
                              type="button"
                              className={
                                mode === 'coach'
                                  ? 'chip chip-active'
                                  : 'chip'
                              }
                              onClick={() =>
                                handleLegModeChange(index, 'coach')
                              }
                            >
                              By Our Coach
                            </button>
                          </div>
                        </div>
                      )
                    })}
                  </div>
                  <p className="field-helper">
                    If you select Train/Other, the system will only charge
                    within-city usage and full day in the next city, similar to
                    the Paris–Zurich example.
                  </p>
                </div>
              )}

              {/* Itinerary / remarks */}
              <div className="form-section">
                <div className="form-section-header">
                  <h3>Itinerary &amp; remarks</h3>
                  <p>
                    Attach days in each city, special notes and train/coach
                    preferences
                  </p>
                </div>
                <textarea
                  rows={3}
                  className="remarks-textarea"
                  placeholder="Attach your itinerary details here for a more refined proposal (you will receive an email within 12 hours)."
                  value={form.remarks}
                  onChange={(e) =>
                    setForm((prev) => ({ ...prev, remarks: e.target.value }))
                  }
                />
              </div>

              {/* Passenger & contact details */}
              <div className="booking-card-header" style={{ borderRadius: 0, marginTop: 16 }}>
                <span className="booking-card-header-title">Passenger details</span>
                <span className="booking-card-header-step">Step 2 of 2</span>
              </div>
              <div className="form-section">
                <div className="form-section-header">
                  <h3>Guest details</h3>
                  <p>Used when user is not logged in</p>
                </div>
                <div className="guest-grid">
                  <div className="booking-field">
                    <label>Guest name</label>
                    <input
                      type="text"
                      value={form.guestName}
                      onChange={(e) =>
                        setForm((prev) => ({
                          ...prev,
                          guestName: e.target.value,
                        }))
                      }
                    />
                  </div>
                  <div className="booking-field">
                    <label>Email</label>
                    <input
                      type="email"
                      value={form.guestEmail}
                      onChange={(e) =>
                        setForm((prev) => ({
                          ...prev,
                          guestEmail: e.target.value,
                        }))
                      }
                    />
                  </div>
                  <div className="booking-field">
                    <label>Phone</label>
                    <input
                      type="text"
                      value={form.guestPhone}
                      onChange={(e) =>
                        setForm((prev) => ({
                          ...prev,
                          guestPhone: e.target.value,
                        }))
                      }
                    />
                  </div>
                  <div className="booking-field small">
                    <label>Country</label>
                    <input
                      type="text"
                      value={form.guestCountry}
                      onChange={(e) =>
                        setForm((prev) => ({
                          ...prev,
                          guestCountry: e.target.value,
                        }))
                      }
                    />
                  </div>
                </div>
              </div>

              {/* Actions */}
              <div className="actions-row">
                <button
                  type="button"
                  className="primary-button"
                  onClick={handleGetQuote}
                  disabled={submitting}
                >
                  {submitting ? 'Preparing quotation…' : 'Preview quotation'}
                </button>
                <button
                  type="button"
                  className="outline-button"
                  onClick={handleSubmitRequest}
                  disabled={submitting}
                >
                  {submitting ? 'Submitting…' : 'Submit enquiry &amp; email quote'}
                </button>
                <button
                  type="button"
                  className="download-button"
                  onClick={() => handleDownloadQuote(quote)}
                  disabled={!quote}
                >
                  Download quotation
                </button>
              </div>

              {quoteError && <p className="error-text">{quoteError}</p>}
            </div>
          </div>

          {/* Right side: dynamic fleet + live quotation preview */}
          <aside className="transport-hero-right">
            <h2 className="right-title">Fleet from admin panel</h2>
            {vehiclesLoading && (
              <p className="field-helper">Loading vehicles…</p>
            )}
            {vehiclesError && <p className="error-text">{vehiclesError}</p>}
            {!vehiclesLoading && !vehiclesError && (
              <div className="fleet-groups">
                {Object.entries(groupedFleet).map(([category, list]) => (
                  <div key={category} className="fleet-group">
                    <p className="fleet-group-title">{category}</p>
                    {list.map((v) => (
                      <div key={v.id} className="vehicle-card">
                        <div className="vehicle-card-header">
                          <div>
                            <p className="vehicle-name">
                              {v.name}{' '}
                              {v.capacity_seats
                                ? `• ${v.capacity_seats} Pax`
                                : ''}
                            </p>
                            {v.description && (
                              <p className="vehicle-description">
                                {v.description}
                              </p>
                            )}
                          </div>
                          {v.image && (
                            <img
                              src={v.image}
                              alt={v.name}
                              className="vehicle-thumb"
                            />
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                ))}
              </div>
            )}

            {/* Live quotation preview */}
            {quote && (
              <div className="quote-card">
                <div className="quote-header">
                  <p className="quote-title">Live transport quotation</p>
                  {quote.vehicle && (
                    <p className="quote-vehicle">
                      {quote.vehicle.name}{' '}
                      {quote.vehicle.capacity_seats
                        ? `• ${quote.vehicle.capacity_seats} Pax`
                        : ''}
                    </p>
                  )}
                </div>
                <div className="quote-table-wrapper">
                  <table className="quote-table">
                    <thead>
                      <tr>
                        <th>Day</th>
                        <th>City / Route</th>
                        <th>Description</th>
                        <th>Vehicle</th>
                        <th className="quote-amount-col">Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      {quote.lineItems && quote.lineItems.length > 0 ? (
                        quote.lineItems.map((item, idx) => (
                          <tr key={idx}>
                            <td>{item.day}</td>
                            <td>{item.city}</td>
                            <td>{item.description}</td>
                            <td>{item.vehicle_display}</td>
                            <td className="quote-amount-col">
                              {item.amount != null
                                ? `${item.currency || quote.currency} ${
                                    item.amount
                                  }`
                                : '-'}
                            </td>
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan={5} className="quote-empty">
                            No line items returned from API.
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
                {quote.totalAmount != null && (
                  <div className="quote-total">
                    <span>Total</span>
                    <strong>
                      {quote.currency} {quote.totalAmount}
                    </strong>
                  </div>
                )}
                <p className="quote-footnote">
                  Proposal format matches your admin quotation logic. For Paris
                  → Zurich by train vs coach, configure city routes and pricing
                  in the backend &quot;Transports&quot; master.
                </p>
              </div>
            )}
          </aside>
        </section>
      </main>
    </div>
  )
}

export default App
