import 'fake-indexeddb/auto';
import { afterEach, beforeEach } from 'vitest';
import { db } from '../../resources/js/data/db';

beforeEach(async () => {
    await db.delete();
    await db.open();
});

afterEach(() => {
    db.close();
});
