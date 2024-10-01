document.addEventListener('DOMContentLoaded', function() {
    const serviceItems = document.querySelectorAll('.services-overview .service-item');
    const serviceImages = document.querySelectorAll('.services-overview .service-image');

    serviceItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            const index = this.getAttribute('data-index');
            serviceImages.forEach(img => img.classList.remove('active'));
            document.querySelector(`.service-image[data-index="${index}"]`).classList.add('active');
        });
    });
});