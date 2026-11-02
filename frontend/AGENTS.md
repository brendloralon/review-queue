# Review Queue — Frontend

Vue 3.5 + Vite + TypeScript SPA consuming the Laravel API. Shared contracts (API shape, error codes, one-home map, git policy) are in the repository root `CLAUDE.md`; read it first.

## Language Baseline

- `<script setup lang="ts">` only; no Options API.
- Use Vue 3.5 features where they make code clearer: type-based `defineProps`/`defineEmits`, reactive props destructure with defaults, `defineModel`, `useTemplateRef`, `useId` for label/input pairing.
- TypeScript strict, no `any`. Explicit return types on exported functions and composables.

## Structure

| Path | Responsibility |
|---|---|
| `src/api/` | `fetch` wrappers, one module per resource. Base URL from `import.meta.env.VITE_API_BASE_URL`. Non-2xx responses throw a typed `ApiError` (status, message, validation errors). |
| `src/types/` | Types mirroring the API Resources exactly |
| `src/composables/` | `useCamelCase.ts`; each owns its loading, error, and data |
| `src/views/` | Route components, lazy-loaded in the router |
| `src/components/` | `PascalCase.vue` presentational components |
| `src/utils/` | Pure formatting helpers and display labels |

- Components never call `fetch` directly.
- No global store; state lives in composables and the URL.

## Data and State

- Every async view handles loading, error (with a retry action), and empty states.
- Abort superseded list requests with `AbortController` so a stale response never overwrites a newer one.
- After a review succeeds, refetch the item and the list; no optimistic updates.
- `useQueueQuery` is the only reader/writer of filter, search, and sort in `route.query`. Invalid values fall back to defaults. Search input is debounced.

## Accessibility

- Semantic elements (`button`, `form`, `label`, `dialog` roles); every input has a label.
- The item drawer has `role="dialog"` and `aria-modal="true"`, traps focus, closes on Esc, and returns focus to the element that opened it.
- Status is never conveyed by color alone.

## Styling

- Scoped CSS in single-file components; shared design tokens as CSS custom properties in one global stylesheet. No Tailwind or UI libraries.

## Checks and Tests

- Vitest + `@vue/test-utils`; tests in `__tests__/` next to the code they cover.
- Before committing: `npm run type-check`, `npm run lint` (applies fixes), `npm run test`.
- Don't add Pinia, Axios, UI kits, or other dependencies without approval.
