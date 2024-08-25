// Standard-Arrays für Video- und Bildformate
let videoFormats = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'wmv', 'flv', 'm4v'];
let imageFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff'];

// Diese Arrays können überschrieben werden, z.B.:
// videoFormats = ['mp4', 'webm']; // falls nur diese Formate unterstützt werden sollen

export function initVenoBox () {
    // Erstelle einen Selektor-String für alle unterstützten Formate
    const allFormats = [...videoFormats, ...imageFormats];
    const selector = allFormats.map(format => `a[href$=".${format}"]`).join(',');

    new VenoBox({
        selector: selector,
        infinigall: true,
        maxWidth: '80%',
        numeration: true,
        spinner: 'flow',
        initialScale: 0.9,
        transitionSpeed: 200,
        fitView: true,
    });
}

export function initVideoLightbox() {
    const videoSelector = videoFormats.map(format => `a[href$=".${format}"]`).join(',');
    const videoLinks = document.querySelectorAll(videoSelector);

    videoLinks.forEach(link => {
        link.setAttribute('data-autoplay', 'true');
        link.setAttribute('data-vbtype', 'video');
        link.setAttribute('data-ratio', 'full');
    });
}


export function initImageLightbox () {
    const imageSelector = imageFormats.map(format => `a[href$=".${format}"]`).join(',');
    const imageLinks = document.querySelectorAll(imageSelector);

    imageLinks.forEach(link => {
        // Hier können Sie beliebige Parameter für Bildlinks setzen
        // Beispiel:
        // link.setAttribute('data-gall', 'gallery1');
    });
}
