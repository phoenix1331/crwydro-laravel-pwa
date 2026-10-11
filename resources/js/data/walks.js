import { locale } from '../i18n';

/**
 * Featured walks, Welsh name first. Route lines are OpenStreetMap data
 * (ODbL) in public/routes/{id}.geojson, precached for offline use.
 */
export const AREAS = {
    eryri: { cy: 'Eryri', en: 'Snowdonia' },
    bannau: { cy: 'Bannau Brycheiniog', en: 'Brecon Beacons' },
    penfro: { cy: 'Arfordir Penfro', en: 'Pembrokeshire Coast' },
    canolbarth: { cy: 'Canolbarth Cymru a’r Gororau', en: 'Mid Wales and the border' },
};

export const WALKS = [
    {
        id: 'yr-wyddfa-llanberis',
        cy: 'Yr Wyddfa: Llwybr Llanberis',
        en: 'Snowdon via the Llanberis Path',
        area: 'eryri',
        note: 'The busiest mountain in Wales, and a good first test route.',
        noteCy: 'Mynydd prysuraf Cymru, a llwybr prawf cyntaf da.',
    },
    {
        id: 'cwm-idwal',
        cy: 'Cwm Idwal, dan y Glyderau',
        en: 'Cwm Idwal, beneath Tryfan and the Glyderau',
        area: 'eryri',
        note: 'Rocky, remote cwm under Tryfan and the Glyderau: classic dead zone walking.',
        noteCy: 'Cwm creigiog, anghysbell o dan Dryfan a’r Glyderau: cerdded clasurol heb signal.',
    },
    {
        id: 'cadair-idris-minffordd',
        cy: 'Cadair Idris: Llwybr Minffordd',
        en: 'Cadair Idris via the Minffordd Path',
        area: 'eryri',
        note: 'Llyn Cau and the cwm make good photo stops.',
        noteCy: 'Mae Llyn Cau a’r cwm yn lefydd da i aros am lun.',
    },
    {
        id: 'pen-y-fan-pont-ar-daf',
        cy: 'Pen y Fan o Bont ar Daf',
        en: 'Pen y Fan from Pont ar Daf',
        area: 'bannau',
        note: 'Short and popular; signal comes and goes.',
        noteCy: 'Byr a phoblogaidd; mae’r signal yn mynd a dod.',
    },
    {
        id: 'arfordir-penfro',
        cy: 'Llwybr Arfordir Penfro',
        en: 'Pembrokeshire Coast Path',
        area: 'penfro',
        note: 'Coastal flowers and seabirds on the Wales Coast Path.',
        noteCy: 'Blodau’r arfordir ac adar môr ar Lwybr Arfordir Cymru.',
    },
    {
        id: 'clawdd-offa',
        cy: 'Llwybr Clawdd Offa',
        en: "Offa's Dyke Path",
        area: 'canolbarth',
        note: 'Multi-day trek along the border.',
        noteCy: 'Taith aml-ddiwrnod ar hyd y ffin.',
    },
    {
        id: 'llwybr-glyndwr',
        cy: 'Llwybr Glyndŵr',
        en: "Glyndŵr's Way",
        area: 'canolbarth',
        note: 'Long, quiet and very little signal.',
        noteCy: 'Hir, tawel a phrin iawn o signal.',
    },
];

/**
 * Quick picks for tagging, per area. `title` fills the entry title with the
 * Welsh name first and English in brackets.
 */
export const LOOK_OUT = {
    eryri: [
        { cy: "Lili'r Wyddfa", en: 'Snowdon lily', tag: 'flower', note: 'Rare and protected: photograph only, never pick.', noteCy: 'Prin a gwarchodedig: tynnwch lun yn unig, peidiwch byth â’i chasglu.' },
        { cy: 'Pabi Cymreig', en: 'Welsh poppy', tag: 'flower' },
        { cy: 'Mwyalchen y mynydd', en: 'Ring ouzel', tag: 'bird' },
        { cy: 'Cigfran', en: 'Raven', tag: 'bird' },
        { cy: 'Gafr wyllt', en: 'Feral goat', tag: 'other' },
    ],
    bannau: [
        { cy: 'Barcud coch', en: 'Red kite', tag: 'bird' },
        { cy: 'Corhedydd y waun', en: 'Meadow pipit', tag: 'bird' },
        { cy: 'Grug', en: 'Heather', tag: 'flower' },
        { cy: 'Merlen fynydd', en: 'Mountain pony', tag: 'other' },
    ],
    penfro: [
        { cy: 'Brân goesgoch', en: 'Chough', tag: 'bird' },
        { cy: 'Pâl', en: 'Puffin', tag: 'bird' },
        { cy: 'Clustog Fair', en: 'Thrift', tag: 'flower' },
        { cy: 'Morlo llwyd', en: 'Grey seal', tag: 'other' },
    ],
    canolbarth: [
        { cy: 'Barcud coch', en: 'Red kite', tag: 'bird' },
        { cy: 'Bwncath', en: 'Buzzard', tag: 'bird' },
        { cy: 'Gylfinir', en: 'Curlew', tag: 'bird' },
        { cy: 'Pabi Cymreig', en: 'Welsh poppy', tag: 'flower' },
    ],
};

export const ROUTE_IDS = WALKS.map((walk) => walk.id);

export function walkById(id) {
    return WALKS.find((walk) => walk.id === id) ?? null;
}

export function walkLabel(id) {
    const walk = walkById(id);

    return walk ? `${walk.cy} (${walk.en})` : null;
}

// With no walk chosen, a short list of what walkers see most across Wales
const COMMON = ['Barcud coch', 'Pabi Cymreig', 'Bwncath', 'Grug', 'Brân goesgoch', "Lili'r Wyddfa"];

export function lookOutFor(routeId) {
    const walk = walkById(routeId);

    if (walk) {
        return LOOK_OUT[walk.area];
    }

    const all = Object.values(LOOK_OUT).flat();

    return COMMON.map((name) => all.find((item) => item.cy === name)).filter(Boolean);
}

export function walkNote(walk) {
    return locale() === 'cy' ? walk.noteCy ?? walk.note : walk.note;
}

export function routeUrl(id) {
    return `/routes/${id}.geojson`;
}
