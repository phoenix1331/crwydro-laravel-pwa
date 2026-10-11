import { monotonicFactory } from 'ulid';

// Sortable, unique even when several are minted in the same millisecond
export const ulid = monotonicFactory();
