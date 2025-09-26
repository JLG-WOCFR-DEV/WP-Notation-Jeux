(function() {
    if (window.jlgAllInOneInit) {
        return;
    }

    let observer = null;
    let blocksInitialized = false;

    const observeBlock = (block) => {
        const animationsEnabled = block.dataset.animationsEnabled === 'true' || block.classList.contains('animate-in');

        if (!animationsEnabled) {
            return;
        }

        if ('IntersectionObserver' in window) {
            if (!observer) {
                observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.2
                });
            }

            observer.observe(block);
        } else {
            block.classList.add('is-visible');
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
                    if (tagline.dataset.lang === selectedLang) {
                        tagline.style.display = 'block';
                    } else {
                        tagline.style.display = 'none';
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

        blocksInitialized = true;
    };

    const onReadyStateChange = () => {
        if (!blocksInitialized) {
            initBlocks();
        }
    };

    const onDOMContentLoaded = () => {
        initBlocks();
        document.removeEventListener('DOMContentLoaded', onDOMContentLoaded);
    };

    const onWindowLoad = () => {
        initBlocks();
        window.removeEventListener('load', onWindowLoad);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onDOMContentLoaded);
    } else {
        initBlocks();
    }

    window.addEventListener('load', onWindowLoad);
    document.addEventListener('readystatechange', onReadyStateChange);

    window.jlgAllInOneInit = initBlocks;
})();
