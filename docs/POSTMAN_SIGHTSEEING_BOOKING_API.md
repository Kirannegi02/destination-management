# Sightseeing Booking API – Postman Guide

All sightseeing and sightseeing-booking endpoints require **JWT authentication**.  
Use your app’s base URL, e.g. `http://localhost` or `https://your-domain.com`.

---

## 1. Get a JWT token (login)

Use your existing auth flow, for example:

- **POST** `{{base_url}}/api/auth/send-otp`  
  Body (JSON): `{ "email": "user@example.com" }` or phone as per your API.

- **POST** `{{base_url}}/api/auth/verify-otp`  
  Body (JSON): `{ "email": "user@example.com", "otp": "123456" }`  
  Response will contain a `token`. Copy it.

In Postman, for every request below, add:

- **Header:** `Authorization` = `Bearer YOUR_TOKEN_HERE`  
  (or in Postman: Auth tab → Type: Bearer Token → Token: paste token)

---

## 2. Sightseeing endpoints (to choose what to book)

| Method | URL | Description |
|--------|-----|-------------|
| GET | `{{base_url}}/api/sightseeings` | List sightseeings (optional: `?country=Switzerland&city=Zurich&per_page=15`) |
| GET | `{{base_url}}/api/sightseeings/1` | Get one sightseeing (replace `1` with ID) |
| GET | `{{base_url}}/api/sightseeings/1/price-availability?date=2026-03-15&pax_count=4` | Get price & availability for a date and pax (optional: `&sightseeing_option_id=2`) |

**Example – Price / availability**

- **GET**  
  `http://localhost/api/sightseeings/1/price-availability?date=2026-03-15&pax_count=4`
- **Headers:** `Authorization: Bearer YOUR_TOKEN`
- **Query params:**  
  - `date` (required): YYYY-MM-DD, today or future  
  - `pax_count` (required): integer ≥ 1  
  - `sightseeing_option_id` (optional): ID of option/variation

---

## 3. Sightseeing booking endpoints

### List my sightseeing bookings

- **Method:** GET  
- **URL:** `{{base_url}}/api/sightseeing-bookings`
- **Headers:** `Authorization: Bearer YOUR_TOKEN`
- **Query params (optional):**
  - `status`: `all` | `pending` | `confirmed` | `cancelled`
  - `per_page`: 1–100 (default 15)

**Example:**  
`http://localhost/api/sightseeing-bookings?status=pending&per_page=10`

---

### Create a sightseeing booking

- **Method:** POST  
- **URL:** `{{base_url}}/api/sightseeing-bookings`
- **Headers:**
  - `Authorization: Bearer YOUR_TOKEN`
  - `Content-Type: application/json`
- **Body (raw JSON):**

```json
{
  "sightseeing_id": 1,
  "sightseeing_option_id": 2,
  "date": "2026-03-15",
  "pax_count": 4,
  "guest_name": "John Doe",
  "guest_phone": "+41 79 123 45 67",
  "special_requests": "Window seat if possible",
  "guests_details": [
    {
      "name": "Jane Doe",
      "country": "Switzerland",
      "phone": "+41 79 000 00 00"
    }
  ]
}
```

**Required:** `sightseeing_id`, `date`, `pax_count`  
**Optional:** `sightseeing_option_id`, `guest_name`, `guest_phone`, `special_requests`, `guests_details`

---

### Get one sightseeing booking

- **Method:** GET  
- **URL:** `{{base_url}}/api/sightseeing-bookings/1`  
  (replace `1` with the booking ID)
- **Headers:** `Authorization: Bearer YOUR_TOKEN`

---

### Cancel a sightseeing booking

- **Method:** POST  
- **URL:** `{{base_url}}/api/sightseeing-bookings/1/cancel`  
  (replace `1` with the booking ID)
- **Headers:** `Authorization: Bearer YOUR_TOKEN`
- **Body:** none (or empty JSON `{}`)

---

## 4. Postman setup summary

1. **Environment**
   - Create an environment with variable `base_url` = `http://localhost` (or your API URL).
   - Add variable `token` and paste the JWT after login.

2. **Authorization**
   - For each request (or at folder level):  
     **Auth** → Type: **Bearer Token** → Token: `{{token}}`.

3. **Quick test order**
   - Login (verify-otp) → copy token into `token` variable.
   - **GET** `/api/sightseeings` – get a `sightseeing_id` (and optional `sightseeing_option_id` from `options` in the response).
   - **GET** `/api/sightseeings/1/price-availability?date=2026-03-15&pax_count=4` – check price.
   - **POST** `/api/sightseeing-bookings` – create a booking (use same IDs and date/pax).
   - **GET** `/api/sightseeing-bookings` – list bookings.
   - **GET** `/api/sightseeing-bookings/1` – get one booking (use ID from list).
   - **POST** `/api/sightseeing-bookings/1/cancel` – cancel that booking (if still pending).

---

## 5. Example responses

**List sightseeing-bookings (GET /api/sightseeing-bookings)**

```json
{
  "success": true,
  "message": "Sightseeing bookings retrieved successfully.",
  "data": [
    {
      "id": 1,
      "sightseeing_id": 1,
      "sightseeing_title": "Mt. Titlis",
      "sightseeing_option_id": 2,
      "sightseeing_option_name": "Mt. Titlis with Ice Flyer",
      "date": "2026-03-15",
      "pax_count": 4,
      "price": 400.00,
      "currency": "CHF",
      "price_formatted": "CHF 400.00",
      "guest_name": "John Doe",
      "guest_phone": "+41 79 123 45 67",
      "guests_details": [],
      "special_requests": "Window seat",
      "booking_conditions": "...",
      "status": "pending",
      "created_at": "2026-02-28T12:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

**Create booking (POST /api/sightseeing-bookings)** – success 201:

```json
{
  "success": true,
  "message": "Sightseeing booking created successfully.",
  "data": {
    "id": 1,
    "sightseeing_id": 1,
    "sightseeing_title": "Mt. Titlis",
    "sightseeing_option_id": 2,
    "sightseeing_option_name": "Mt. Titlis with Ice Flyer",
    "date": "2026-03-15",
    "pax_count": 4,
    "price": 400.00,
    "currency": "CHF",
    "status": "pending",
    "created_at": "2026-02-28T12:00:00.000000Z"
  }
}
```

Use this guide to list and test the sightseeing booking API in Postman.
