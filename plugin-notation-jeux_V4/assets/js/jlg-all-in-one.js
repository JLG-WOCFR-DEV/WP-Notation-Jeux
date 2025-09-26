'use strict';

(function() {
    if (window.jlgAllInOneInit) {
        return;
    }

    const settings = window.jlgAllInOneSettings || {};
    const visibleClass = typeof settings.visibleClass === 'string' && settings.visibleClass !== ''
        ? settings.visibleClass
        : 'is-visible';
    const animationClass = typeof settings.animationClass === 'string' && settings.animationClass !== ''
        ? settings.animationClass
        : 'animate-in';
    const observerThreshold = typeof settings.observerThreshold === 'number'
        ? settings.observerThreshold
        : 0.2;

    let observer = null;

    const observeBlock = (block) => {
        const animationsEnabled = block.dataset.animationsEnabled === 'true' || block.classList.contains(animationClass);

        if (!animationsEnabled) {
            block.classList.add(visibleClass);
            return;
        }

        if ('IntersectionObserver' in window) {
            if (!observer) {
                observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add(visibleClass);
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: observerThreshold
                });
            }

            observer.observe(block);
        } else {
            block.classList.add(visibleClass);
        }
    };

    const bindFlagToggle = (block) => {
        const hasMultipleTaglines = block.dataset.hasMultipleTaglines === 'true';
        if (!hasMultipleTaglines) {
            return;
        }

        const flags = block.querySelectorAll('.jlg-aio-flag');
        const taglines = block.querySelectorAll('.jlg-aio-tagline');

        if (!flags.length || !taglines.length) {
            return;
        }

        flags.forEach((flag) => {
            flag.addEventListener('click', () => {
                const selectedLang = flag.dataset.lang;

                flags.forEach((innerFlag) => {
                    innerFlag.classList.toggle('active', innerFlag === flag);
                });

                taglines.forEach((tagline) => {
                    const matchesSelection = tagline.dataset.lang === selectedLang;

                    tagline.style.display = matchesSelection ? 'block' : 'none';

                    if (matchesSelection) {
                        tagline.removeAttribute('hidden');
                    } else {
                        tagline.setAttribute('hidden', '');
                    }
                });
            });
        });
    };

    const initBlocks = () => {
        const blocks = document.querySelectorAll('.jlg-all-in-one-block');

        if (!blocks.length) {
            return;
        }

        blocks.forEach((block) => {
            if (block.dataset.jlgAioInitialized === 'true') {
                return;
            }

            block.dataset.jlgAioInitialized = 'true';

            bindFlagToggle(block);
            observeBlock(block);
        });
    };

    const readyStates = ['interactive', 'complete'];

    if (readyStates.includes(document.readyState)) {
        initBlocks();
    } else {
        const onDomReady = () => {
            document.removeEventListener('DOMContentLoaded', onDomReady);
            initBlocks();
        };

        document.addEventListener('DOMContentLoaded', onDomReady);
    }

    const onWindowLoad = () => {
        window.removeEventListener('load', onWindowLoad);
        initBlocks();
    };

    window.addEventListener('load', onWindowLoad);

    window.jlgAllInOneInit = initBlocks;
})();
