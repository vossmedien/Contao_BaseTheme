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
        fitView: false,
    });
}

function getVideoAspectRatio(videoUrl, callback) {
    const video = document.createElement('video');
    video.onloadedmetadata = function() {
        const width = this.videoWidth;
        const height = this.videoHeight;
        const ratio = width / height;

        // Definieren Sie die verfügbaren Seitenverhältnisse
        const ratios = {
            '1x1': 1,
            '4x3': 4/3,
            '16x9': 16/9,
            '21x9': 21/9,
            '9x16': 9/16,
            '3x4': 3/4
        };

        // Finden Sie das am besten passende Seitenverhältnis
        let bestMatch = 'custom';
        let minDifference = Infinity;

        for (const [name, value] of Object.entries(ratios)) {
            const difference = Math.abs(ratio - value);
            if (difference < minDifference) {
                minDifference = difference;
                bestMatch = name;
            }
        }

        // Wenn die Differenz zu groß ist, verwenden Sie 'custom'
        if (minDifference > 0.1) {
            bestMatch = 'custom';
            // Setzen Sie benutzerdefinierte CSS-Variablen für das exakte Seitenverhältnis
            const customRatio = (height / width * 100).toFixed(2) + '%';
            const customMaxWidth = `calc(min(var(--vbox-max-width), (100vh - 60px) * ${width} / ${height}))`;
            callback(bestMatch, customRatio, customMaxWidth);
        } else {
            callback(bestMatch);
        }
    };
    video.onerror = function() {
        console.error('Error loading video metadata');
        callback('16x9'); // Fallback to 16:9 if there's an error
    };
    video.src = videoUrl;
}

export function initVideoLightbox() {
    const videoSelector = videoFormats.map(format => `a[href$=".${format}"]`).join(',');
    const videoLinks = document.querySelectorAll(videoSelector);

    videoLinks.forEach(link => {
        link.setAttribute('data-autoplay', 'true');
        link.setAttribute('data-vbtype', 'video');

        const videoUrl = link.getAttribute('href');
        getVideoAspectRatio(videoUrl, (ratio, customRatio, customMaxWidth) => {
            link.setAttribute('data-ratio', ratio);
            if (ratio === 'custom') {
                link.style.setProperty('--custom-aspect-ratio', customRatio);
                link.style.setProperty('--custom-max-width', customMaxWidth);
            }
        });
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
