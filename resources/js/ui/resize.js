export const MAX_EDGE = 1600;
export const THUMB_EDGE = 320;

/**
 * Downscale a camera photo before it is stored: a 12 MP original is several
 * megabytes, a 1600px JPEG a few hundred kilobytes. Drawing to a canvas also
 * drops EXIF, including the camera's GPS tags, and bakes in the orientation.
 */
export async function resizePhoto(file, { maxEdge = MAX_EDGE, thumbEdge = THUMB_EDGE, quality = 0.82 } = {}) {
    const source = await decode(file);

    try {
        const full = await encode(source, maxEdge, quality);
        const thumb = await encode(source, thumbEdge, 0.7);

        return { blob: full.blob, width: full.width, height: full.height, thumb: thumb.blob };
    } finally {
        source.close?.();
    }
}

async function decode(file) {
    if ('createImageBitmap' in window) {
        return createImageBitmap(file, { imageOrientation: 'from-image' });
    }

    const url = URL.createObjectURL(file);

    try {
        const image = new Image();
        image.src = url;
        await image.decode();

        return image;
    } finally {
        URL.revokeObjectURL(url);
    }
}

async function encode(source, maxEdge, quality) {
    const sourceWidth = source.width ?? source.naturalWidth;
    const sourceHeight = source.height ?? source.naturalHeight;
    const scale = Math.min(1, maxEdge / Math.max(sourceWidth, sourceHeight));
    const width = Math.round(sourceWidth * scale);
    const height = Math.round(sourceHeight * scale);

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    canvas.getContext('2d').drawImage(source, 0, 0, width, height);

    const blob = await new Promise((resolve, reject) =>
        canvas.toBlob((result) => (result ? resolve(result) : reject(new Error('Could not encode photo.'))), 'image/jpeg', quality),
    );

    return { blob, width, height };
}
