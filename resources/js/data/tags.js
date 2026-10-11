import { locale } from '../i18n';

export const TAGS = [
    { key: 'flower', en: 'Flower', cy: 'Blodyn' },
    { key: 'bird', en: 'Bird', cy: 'Aderyn' },
    { key: 'fungus', en: 'Fungus', cy: 'Ffwng' },
    { key: 'insect', en: 'Insect', cy: 'Pryfyn' },
    { key: 'view', en: 'View', cy: 'Golygfa' },
    { key: 'other', en: 'Other', cy: 'Arall' },
];

export const TAG_KEYS = TAGS.map((tag) => tag.key);

export function tagLabel(key) {
    const tag = TAGS.find((item) => item.key === key);

    return tag ? tag[locale()] : key;
}
