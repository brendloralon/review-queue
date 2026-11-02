export type ValidationErrors = Record<string, string[]>

export class ApiError extends Error {
  readonly status: number
  readonly validationErrors: ValidationErrors

  constructor(message: string, status: number, validationErrors: ValidationErrors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.validationErrors = validationErrors
  }
}

export interface RequestOptions {
  method?: 'GET' | 'POST'
  body?: unknown
  signal?: AbortSignal
}

const NETWORK_ERROR_MESSAGE =
  'Could not reach the server. Check that the API is running and try again.'

const FALLBACK_MESSAGES: Record<number, string> = {
  404: 'This item could not be found.',
  409: 'This item was changed by someone else.',
  422: 'Some fields are invalid.',
}

const GENERIC_ERROR_MESSAGE = 'Something went wrong. Please try again.'

export function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

export async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  let response: Response

  try {
    response = await fetch(apiUrl(path), {
      method: options.method ?? 'GET',
      headers: requestHeaders(options.body),
      body: options.body === undefined ? undefined : JSON.stringify(options.body),
      signal: options.signal,
    })
  } catch (error) {
    if (isAbortError(error)) {
      throw error
    }
    throw new ApiError(NETWORK_ERROR_MESSAGE, 0)
  }

  const payload = await readJson(response)

  if (!response.ok) {
    throw toApiError(response.status, payload)
  }

  // The API Resources define the response shape and src/types mirrors them,
  // so the boundary is trusted here rather than validated at runtime.
  return payload as T
}

function apiUrl(path: string): string {
  const baseUrl: string | undefined = import.meta.env.VITE_API_BASE_URL

  if (!baseUrl) {
    throw new Error('VITE_API_BASE_URL is not set. See frontend/.env.development.')
  }

  return `${baseUrl}${path}`
}

function requestHeaders(body: unknown): HeadersInit {
  return body === undefined
    ? { Accept: 'application/json' }
    : { Accept: 'application/json', 'Content-Type': 'application/json' }
}

async function readJson(response: Response): Promise<unknown> {
  try {
    return await response.json()
  } catch {
    return null
  }
}

function toApiError(status: number, payload: unknown): ApiError {
  const body = isRecord(payload) ? payload : {}
  const message =
    typeof body.message === 'string' && status < 500
      ? body.message
      : (FALLBACK_MESSAGES[status] ?? GENERIC_ERROR_MESSAGE)
  const validationErrors = isRecord(body.errors) ? (body.errors as ValidationErrors) : {}

  return new ApiError(message, status, validationErrors)
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}
