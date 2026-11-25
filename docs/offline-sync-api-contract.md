# OnTime — Offline-First Doctor Workflow: API Contract

Audience: Angular frontend (Service Worker + Dexie/IndexedDB).
Base URL: `/api/doctor` · Auth: Sanctum bearer token · Role: `doctor` only.

## 1. Common rules

**Headers (every request)**

```
Authorization: Bearer <sanctum_token>
Accept: application/json        ← required, otherwise errors come back as HTML redirects
Content-Type: application/json  ← multipart/form-data for attachments
```

**Response envelope (unchanged)**

```json
{ "status": true, "errNum": "0000", "msg": "...", "<key>": { ... }, "replayed": false }
```

**Offline fields (all clinical POSTs)**

| Field | Type | Rule |
|---|---|---|
| `client_uuid` | UUID v4 (`crypto.randomUUID()`) | Optional. Generate **once** when the doctor saves, store it with the queued item, **reuse the same value on every retry**. |
| `recorded_at` | ISO-8601 with offset, e.g. `2026-11-25T13:40:12+02:00` | Optional. The moment the doctor pressed save. Must be within the **last 7 days** and not more than **5 min in the future**. Server stores it in UTC. Omitted → server time. |

Online requests may send the same fields — that's recommended, so a timed-out online save is also safe to retry.

**Status codes**

| Code | Meaning | Queue action |
|---|---|---|
| `201` + `replayed:false` | Created | Mark synced, save server `id` |
| `200` + `replayed:true` | Already existed (earlier attempt succeeded) | Mark synced, save server `id` — **not an error** |
| `401` | Token invalid / revoked | **Pause** queue, keep items, ask doctor to log in, resume with new token |
| `403` | Not a doctor | Pause queue, show error |
| `404` | Parent not found (see §3) | Parent not synced yet → retry later; if parent is synced and still 404 → dead-letter |
| `409` `errNum:"E409"` | `client_uuid` already used under a different parent | Bug — dead-letter, never retry |
| `413` | File too large for server | Dead-letter |
| `422` | Validation (incl. `recorded_at` too old) | Dead-letter, show to doctor to re-enter |
| `429` / `5xx` / network error / timeout | Transient | Retry with exponential backoff (2s, 4s, 8s … max 5 min) + jitter |

Sanctum tokens currently **do not expire** (`expiration: null`), so `401` only happens on logout/revocation. Never discard queued items on `401`.

## 2. Bootstrap (read-only cache)

### `GET /offline-pack`

Call: on login, on app start when online, and every ~30 min while online. Replace the cached tables entirely on each success.

```json
{
  "status": true, "errNum": "0000", "msg": "success",
  "pack": {
    "generated_at": "2026-11-25T11:58:00+00:00",
    "valid_until": "2026-11-26T23:59:59+00:00",
    "dates": ["2026-11-25", "2026-11-26"],
    "appointments": [
      {
        "id": 812, "patient_id": 55,
        "visit_type": "checkup", "status": "confirmed", "payment_status": "paid",
        "notes": null,
        "date": "2026-11-25", "start_time": "14:00:00", "end_time": "14:20:00",
        "clinic": { "id": 2, "name": "فرع المعادي" }
      }
    ],
    "patients": [
      {
        "id": 55, "name": "أحمد علي", "phone": "01000000000",
        "profile": {
          "id": 31, "date_of_birth": "1980-04-02", "gender": "male",
          "blood_type": "A+", "has_hypertension": true, "has_diabetes": false,
          "has_heart_disease": false, "has_previous_stroke": false,
          "medical_notes": "حساسية بنسلين"
        },
        "recent_records": [
          {
            "id": 401, "client_uuid": null, "appointment_id": 700,
            "record_type": "checkup", "diagnosis": "التهاب حلق",
            "treatment": "...", "notes": null,
            "recorded_at": "2026-11-10T10:05:00+00:00",
            "prescriptions": [
              { "id": 90, "client_uuid": null, "notes": null,
                "recorded_at": "2026-11-10T10:07:00+00:00",
                "items": [ { "id": 300, "medicine_name": "Augmentin 1g", "dosage": "1 قرص", "frequency": "كل 12 ساعة", "duration": "7 أيام" } ] }
            ],
            "attachments": [
              { "id": 12, "client_uuid": null, "type": "xray", "original_name": "chest.jpg",
                "url": "https://.../storage/medical-attachments/abc.jpg",
                "recorded_at": "2026-11-10T10:09:00+00:00" }
            ]
          }
        ]
      }
    ]
  }
}
```

Notes:
- Normalized: one entry per patient even with two visits. Suggested Dexie stores: `appointments(id, patient_id, date)`, `patients(id)`.
- `recent_records` = the last **5** records per patient, newest first.
- Statuses included: `confirmed`, `in_progress`, `completed` (cancelled/no-show excluded).
- Attachment `url`s are metadata only — images are not cached unless you choose to.
- Dates are computed in the server timezone (UTC).
- When showing history offline, merge cached `recent_records` with locally queued (unsynced) items, sorted by `recorded_at`.

### `GET /medicines/offline` (ETag)

```
GET /api/doctor/medicines/offline
If-None-Match: "meds-3f2a…"        ← the ETag you stored last time (omit on first call)
```

- `304 Not Modified` (empty body) → keep the cached list.
- `200` →
```json
{ "status": true, "errNum": "0000", "msg": "success",
  "medicines": [ { "id": 1, "commercial_name_en": "Panadol", "commercial_name_ar": "بانادول", "scientific_name": "Paracetamol", "price_egp": "45.00" } ],
  "version": "3f2a…" }
```
Store the `ETag` **response header** (exact string incl. quotes) and replace the `medicines` store. Call once per day / app start. Search offline locally (prefix match on `commercial_name_en` first, then contains on all three names — same ranking as `/medicines/search`). If the browser's HTTP cache is in front, Angular may see a `200` from cache instead of `304` — either is fine.

## 3. Append-only writes (queued)

Offline: **only these POSTs are allowed**. Editing/deleting, `PUT /doctor/patients/{id}/medical-info`, and booking flows stay online-only.

### 3.1 Diagnosis / medical record

`POST /appointments/{appointmentId}/medical-record`

```json
{
  "client_uuid": "6c1d6c0e-4c5b-4f0f-9a5c-1b0f6f0b2d11",
  "recorded_at": "2026-11-25T13:40:12+02:00",
  "record_type": "checkup",
  "diagnosis": "التهاب حلق",
  "treatment": "راحة وسوائل",
  "notes": null
}
```
`record_type` ∈ `checkup | consultation | followup`; `diagnosis` required, max 255.

Response `201` (or `200` replay):
```json
{ "status": true, "errNum": "0000", "msg": "تم حفظ التشخيص",
  "medical_record": { "id": 512, "client_uuid": "6c1d…", "appointment_id": 812, "patient_id": 55,
    "record_type": "checkup", "diagnosis": "التهاب حلق", "treatment": "راحة وسوائل", "notes": null,
    "recorded_at": "2026-11-25T11:40:12.000000Z", "created_at": "...", "updated_at": "..." },
  "replayed": false }
```

### 3.2 Prescription

Parent known by server id: `POST /medical-records/{medicalRecordId}/prescription`
Parent created offline: `POST /medical-records/uuid/{recordClientUuid}/prescription`

```json
{
  "client_uuid": "a8f5…",
  "recorded_at": "2026-11-25T13:42:00+02:00",
  "notes": "بعد الأكل",
  "items": [
    { "medicine_name": "Augmentin 1g", "dosage": "1 قرص", "frequency": "كل 12 ساعة", "duration": "7 أيام" }
  ]
}
```
`items` required (min 1); `medicine_name` max 255; others max 100. Items are saved atomically with the prescription — no per-item UUIDs.

Response key: `prescription` (includes `items[]`).

### 3.3 Attachment (X-ray / lab result)

Parent by id: `POST /medical-records/{medicalRecordId}/attachments`
Parent by UUID: `POST /medical-records/uuid/{recordClientUuid}/attachments`

`multipart/form-data`:

| Field | Value |
|---|---|
| `file` | Blob from IndexedDB, with filename — `jpg, jpeg, png, pdf`, **server max 10 MB** |
| `type` | `xray` \| `lab_result` |
| `client_uuid` | UUID |
| `recorded_at` | ISO-8601 |

Response key: `attachment` → `{ id, client_uuid, type, url, original_name, recorded_at }`.

Server checks `client_uuid` **before** storing the file, so retries never create duplicate files.

## 4. Visit completion (online, idempotent)

`POST /appointments/{appointmentId}/complete` — no body.
- First call: `200`, `msg: "تم إكمال الكشف بنجاح"`, `replayed:false`.
- Already completed: `200`, `msg: "الكشف مكتمل بالفعل"`, `replayed:true` (no duplicate real-time event).
- Not `in_progress`/`completed`: `422`, `errNum:"E108"`.

This is a status change, not an append — keep it **online-only** (disable the button offline), but it's safe to retry on timeout.

## 5. Sync queue rules (frontend)

1. **One queue, FIFO, one request at a time.** Each item: `{ client_uuid, kind, endpoint, payload | blobKey, parent_client_uuid?, recorded_at, attempts, status }`.
2. **Order:** text first, files last. Within a visit: medical record → prescriptions → attachments. Don't send a child while its parent is still pending; a `404` on a `/uuid/` route means the parent hasn't reached the server yet.
3. **Parent reference:** if the parent record has a server `id`, use the `{id}` route; otherwise use the `/uuid/{parent_client_uuid}/` route. Both are equivalent.
4. **Same `client_uuid` on every retry.** Never regenerate it — that's what prevents duplicates.
5. **Attachments:** enforce **5 MB** client-side before queueing (compress images if larger). Server hard limit is 10 MB. **Delete the Blob from IndexedDB as soon as the server answers `201` or `200`.**
6. **Triggers:** `online` event, app start, Background Sync (`sync` event in the Service Worker where supported), and a timer every 30–60 s while items are pending.
7. **Dead-letter** (`409`, `413`, `422`, or 404 with a synced parent): stop retrying that item and its children, keep it visible to the doctor with the server `msg`.
8. **Read-only offline:** hide edit/delete buttons when `navigator.onLine === false` or the last request failed with a network error.
9. **After queue drains:** refetch `/offline-pack` so cached history includes the server ids.
