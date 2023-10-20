function prepareAOSAttributes() {
    let elements = document.querySelectorAll("*:not([data-aos])");

    elements.forEach(function (element) {
        let classList = Array.from(element.classList);
        let animationClasses = classList.filter((cls) =>
            cls.startsWith("animate__")
        );

        if (animationClasses.length) {
            // Remove the animation classes from the element
            animationClasses.forEach((cls) => element.classList.remove(cls));

            // Add the data-aos attribute with the value of the removed class
            element.setAttribute("data-aos", animationClasses.join(" "));
        }
    });
}
function initAnimations() {
    prepareAOSAttributes();

    const animatedElements = Array.from(document.querySelectorAll("[data-aos]"));

    const rootStyle = getComputedStyle(document.documentElement);
    const repeatAnimation = rootStyle.getPropertyValue('--animate-repeat').trim() === '1';
    const sequenceAnimation = rootStyle.getPropertyValue('--animate-sequence').trim() === '1';

    const handleIntersect = (entries, observer) => {
        let delayAccumulator = 0;

        entries.forEach((entry) => {
            const el = entry.target;

            if (entry.isIntersecting) {
                if (!el.dataset.animated || repeatAnimation) {
                    setTimeout(() => {
                        const animationName = el.getAttribute("data-aos");
                        el.classList.add("animate__animated", animationName);
                        el.dataset.animated = "true";
                    }, sequenceAnimation ? delayAccumulator : 0);

                    if (sequenceAnimation) {
                        delayAccumulator += 100;
                    }
                }
            } else if (repeatAnimation) {
                const animationName = el.getAttribute("data-aos");
                el.classList.remove("animate__animated", animationName);
                delete el.dataset.animated;
            }
        });
    };

    const options = {
        root: null,
        rootMargin: "0px 0px 0px 0px",
        threshold: 0.1,
    };

    const observer = new IntersectionObserver(handleIntersect, options);
    animatedElements.forEach((el) => observer.observe(el));
}
