import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError, request } from '../client'

function jsonResponse(status: number, body: unknown): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  })
}

describe('request', () => {
  const fetchMock = vi.fn<typeof fetch>()

  beforeEach(() => {
    vi.stubEnv('VITE_API_BASE_URL', 'http://api.test/api/v1')
    vi.stubGlobal('fetch', fetchMock)
  })

  afterEach(() => {
    vi.unstubAllEnvs()
    vi.unstubAllGlobals()
    fetchMock.mockReset()
  })

  it('returns the parsed body and sends JSON to the base URL', async () => {
    fetchMock.mockResolvedValue(jsonResponse(201, { data: { id: 1 } }))

    const result = await request<{ data: { id: number } }>('/items', {
      method: 'POST',
      body: { title: 'Hi' },
    })

    expect(result).toEqual({ data: { id: 1 } })
    const [url, init] = fetchMock.mock.calls[0] ?? []
    expect(url).toBe('http://api.test/api/v1/items')
    expect(init?.body).toBe('{"title":"Hi"}')
  })

  it('turns a 409 into an ApiError carrying the server message', async () => {
    fetchMock.mockResolvedValue(jsonResponse(409, { message: 'Item 3 has already been approved.' }))

    const error = await request('/items/3/review', { method: 'POST', body: {} }).catch(
      (caught: unknown) => caught,
    )

    expect(error).toBeInstanceOf(ApiError)
    expect(error).toMatchObject({ status: 409, message: 'Item 3 has already been approved.' })
  })

  it('exposes validation errors from a 422', async () => {
    fetchMock.mockResolvedValue(
      jsonResponse(422, {
        message: 'The title field is required.',
        errors: { title: ['The title field is required.'] },
      }),
    )

    const error = await request('/items', { method: 'POST', body: {} }).catch(
      (caught: unknown) => caught,
    )

    expect(error).toMatchObject({
      status: 422,
      validationErrors: { title: ['The title field is required.'] },
    })
  })

  it('hides server error details behind a generic message', async () => {
    fetchMock.mockResolvedValue(jsonResponse(500, { message: 'SQLSTATE[HY000]: database locked' }))

    const error = await request('/items').catch((caught: unknown) => caught)

    expect(error).toMatchObject({ status: 500, message: 'Something went wrong. Please try again.' })
  })

  it('reports network failures as status 0', async () => {
    fetchMock.mockRejectedValue(new TypeError('Failed to fetch'))

    const error = await request('/items').catch((caught: unknown) => caught)

    expect(error).toBeInstanceOf(ApiError)
    expect(error).toMatchObject({ status: 0 })
  })

  it('rethrows aborts untouched so callers can ignore them', async () => {
    fetchMock.mockRejectedValue(new DOMException('Aborted', 'AbortError'))

    const error = await request('/items').catch((caught: unknown) => caught)

    expect(error).toBeInstanceOf(DOMException)
  })
})
