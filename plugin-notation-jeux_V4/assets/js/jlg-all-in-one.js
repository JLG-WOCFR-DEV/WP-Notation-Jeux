(function() {
    if (window.jlgAllInOneInit) {
        return;
    }

    const initializedBlocks = new WeakSet();
    const observersByThreshold = new Map();

    const ensureObserver = (threshold) => {
        if (!('IntersectionObserver' in window)) {
            return null;
        }

        const normalizedThreshold = Math.max(0, Math.min(1, threshold));
        const key = normalizedThreshold.toFixed(2);

        if (!observersByThreshold.has(key)) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, {
                threshold: normalizedThreshold,
            });

            observersByThreshold.set(key, io);
        }

        return observersByThreshold.get(key) || null;
    };

    const observeBlock = (block) => {
        const animationsEnabled = block.dataset.animationsEnabled === 'true' || block.classList.contains('animate-in');
        if (!animationsEnabled) {
            block.classList.add('is-visible');
            return;
        }

        const threshold = Math.max(0, Math.min(1, parseFloat(block.dataset.animationThreshold || '0.2') || 0.2));
        const io = ensureObserver(threshold);

        if (io) {
            io.observe(block);
            return;
        }

        block.classList.add('is-visible');
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
                    tagline.style.display = tagline.dataset.lang === selectedLang ? 'block' : 'none';
                });
            });
        });
    };

    const initBlock = (block) => {
        if (initializedBlocks.has(block)) {
            return;
        }

        initializedBlocks.add(block);
        bindFlagToggle(block);
        observeBlock(block);
    };

    const initBlocks = () => {
        const blocks = document.querySelectorAll('.jlg-all-in-one-block');
        if (!blocks.length) {
            return;
        }

        blocks.forEach(initBlock);
    };

    const handleReady = () => {
        initBlocks();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', handleReady, { once: true });
    } else {
        handleReady();
    }

    window.addEventListener('load', initBlocks);
    document.addEventListener('readystatechange', () => {
        if (document.readyState === 'complete') {
            initBlocks();
        }
    });

    window.jlgAllInOneInit = initBlocks;
})();
