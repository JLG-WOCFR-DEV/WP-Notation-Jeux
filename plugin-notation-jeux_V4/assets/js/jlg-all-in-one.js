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

        const activateFlag = (flag) => {
            if (flag.classList.contains('active')) {
                return;
            }

            const selectedLang = flag.dataset.lang;

            flags.forEach((innerFlag) => {
                const isActive = innerFlag === flag;
                innerFlag.classList.toggle('active', isActive);
                innerFlag.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            taglines.forEach((tagline) => {
                const matchesSelection = tagline.dataset.lang === selectedLang;

                if (matchesSelection) {
                    tagline.removeAttribute('hidden');
                    tagline.setAttribute('aria-hidden', 'false');
                    tagline.style.display = '';
                } else {
                    tagline.setAttribute('hidden', '');
                    tagline.setAttribute('aria-hidden', 'true');
                    tagline.style.display = 'none';
                }
            });
        };

        flags.forEach((flag) => {
            flag.addEventListener('click', (event) => {
                event.preventDefault();
                activateFlag(flag);
            });

            flag.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                activateFlag(flag);
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
