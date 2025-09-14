document.addEventListener("DOMContentLoaded", function() {
    const animatedElements = document.querySelectorAll('.jlg-animate');

    if (!animatedElements.length || !('IntersectionObserver' in window)) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-in-view');
                observer.unobserve(entry.target); // Pour n'animer l'élément qu'une seule fois
            }
        });
    }, {
        threshold: 0.5 // Se déclenche quand 50% de l'élément est visible
    });

    animatedElements.forEach(element => {
        observer.observe(element);
    });
});