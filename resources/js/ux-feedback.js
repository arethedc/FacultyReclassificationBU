const progressBar = (() => {
    const el = document.getElementById('ux-top-progress');
    if (!el) return null;

    let activeCount = 0;
    let value = 0;
    let timer = null;
    let finishTimer = null;

    const set = (next) => {
        value = Math.max(0, Math.min(100, next));
        el.style.transform = `scaleX(${value / 100})`;
    };

    const clearTimers = () => {
        if (timer) clearInterval(timer);
        if (finishTimer) clearTimeout(finishTimer);
        timer = null;
        finishTimer = null;
    };

    const start = () => {
        activeCount += 1;
        if (activeCount > 1) return;

        clearTimers();
        el.dataset.active = '1';
        set(8);
        timer = setInterval(() => {
            if (value < 88) {
                set(value + Math.max(2, (88 - value) * 0.08));
            }
        }, 180);
    };

    const done = () => {
        if (activeCount > 0) activeCount -= 1;
        if (activeCount > 0) return;

        clearTimers();
        set(100);
        finishTimer = setTimeout(() => {
            el.dataset.active = '0';
            set(0);
        }, 260);
    };

    return { start, done };
})();

const finishInitialSkeleton = (() => {
    let finished = false;
    let timer = null;

    const apply = () => {
        if (finished) return;
        finished = true;
        document.documentElement.removeAttribute('data-ux-boot-loading');
        document.documentElement.removeAttribute('data-ux-boot-start');
    };

    return (force = false) => {
        if (finished) return;
        if (force) {
            if (timer) clearTimeout(timer);
            apply();
            return;
        }

        const bootStart = Number(document.documentElement.getAttribute('data-ux-boot-start') || Date.now());
        const elapsed = Date.now() - bootStart;
        const waitMs = Math.max(0, 180 - elapsed);
        if (timer) clearTimeout(timer);
        timer = window.setTimeout(apply, waitMs);
    };
})();

const liveRegion = (() => document.getElementById('ux-live-region'))();
const toastRegion = (() => document.getElementById('ux-toast-region'))();

const announce = (message = '') => {
    if (!liveRegion) return;
    liveRegion.textContent = '';
    window.requestAnimationFrame(() => {
        liveRegion.textContent = String(message || '');
    });
};

let lastToast = { message: '', at: 0 };
const toast = (message, type = 'info', timeout = 3400) => {
    if (!toastRegion || !message) return;
    const now = Date.now();
    if (lastToast.message === message && (now - lastToast.at) < 2500) return;
    lastToast = { message, at: now };

    const item = document.createElement('div');
    item.className = `ux-toast ux-toast-${type}`;
    item.setAttribute('role', 'status');
    item.innerHTML = `<span>${String(message)}</span>`;
    toastRegion.appendChild(item);

    window.requestAnimationFrame(() => item.dataset.show = '1');

    window.setTimeout(() => {
        item.dataset.show = '0';
        window.setTimeout(() => item.remove(), 220);
    }, timeout);
};

const resolvePanel = (target) => {
    if (!target) return null;
    if (target instanceof HTMLElement) return target;
    try {
        return document.querySelector(String(target));
    } catch (error) {
        return null;
    }
};

const panelApi = {
    setLoading(target, isLoading, message = 'Loading results...') {
        const panel = resolvePanel(target);
        if (!panel) return;

        const content = panel.querySelector('[data-ux-panel-content]');
        const skeleton = panel.querySelector('[data-ux-panel-skeleton]');
        panel.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        panel.dataset.uxLoading = isLoading ? '1' : '0';

        if (content && skeleton) {
            content.classList.toggle('hidden', isLoading);
            skeleton.classList.toggle('hidden', !isLoading);
        } else {
            panel.classList.toggle('ux-loading-fade', isLoading);
        }

        if (isLoading) {
            announce(message);
        }
    },
    setRefreshing(target, refreshing, message = 'Refreshing data in background...') {
        const panel = resolvePanel(target);
        if (!panel) return;
        panel.dataset.uxRefreshing = refreshing ? '1' : '0';
        if (refreshing) announce(message);
    },
    showError(target, message = 'Could not update results. Please retry.') {
        const panel = resolvePanel(target);
        if (!panel) return;

        const content = panel.querySelector('[data-ux-panel-content]') || panel;
        const existing = content.querySelector('[data-ux-panel-error]');
        if (existing) existing.remove();

        const errorEl = document.createElement('div');
        errorEl.setAttribute('data-ux-panel-error', '1');
        errorEl.className = 'ux-state ux-state-error mb-4';
        errorEl.innerHTML = `<p class="font-semibold">Update failed</p><p class="mt-1">${String(message)}</p>`;
        content.prepend(errorEl);
        announce(message);
    },
    clearError(target) {
        const panel = resolvePanel(target);
        if (!panel) return;
        panel.querySelectorAll('[data-ux-panel-error]').forEach((node) => node.remove());
    },
};

const ensureInputSpinner = (input) => {
    if (!(input instanceof HTMLElement)) return null;
    const parent = input.parentElement;
    if (!parent) return null;

    if (window.getComputedStyle(parent).position === 'static') {
        parent.style.position = 'relative';
    }

    let spinner = parent.querySelector('[data-ux-input-spinner]');
    if (!spinner) {
        spinner = document.createElement('span');
        spinner.setAttribute('data-ux-input-spinner', '1');
        spinner.setAttribute('aria-hidden', 'true');
        spinner.className = 'ux-input-spinner hidden';
        parent.appendChild(spinner);
        input.classList.add('pr-10');
    }

    return spinner;
};

const setAutoSubmitFormLoading = (form, isLoading, message = 'Updating results...') => {
    if (!(form instanceof HTMLFormElement)) return;

    form.dataset.autoSubmitBusy = isLoading ? '1' : '0';
    form.classList.toggle('ux-form-loading', isLoading);

    const searchInput = form.querySelector('input[type="search"], input[name="q"], input[name="search"], input[data-auto-submit-input="true"]');
    if (searchInput) {
        const spinner = ensureInputSpinner(searchInput);
        if (spinner) spinner.classList.toggle('hidden', !isLoading);
        searchInput.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    form.querySelectorAll('select').forEach((select) => {
        select.classList.toggle('opacity-70', isLoading);
        select.classList.toggle('cursor-wait', isLoading);
        select.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    });

    if (isLoading) announce(message);
};

const setActionButtonLoading = (button, loadingText) => {
    if (!(button instanceof HTMLElement)) return;
    if (button.dataset.uxActionBusy === '1') return;
    button.dataset.uxActionBusy = '1';
    button.classList.add('ux-btn-loading');

    if (!button.dataset.uxOriginalHtml) {
        button.dataset.uxOriginalHtml = button.innerHTML;
    }
    const label = loadingText || button.dataset.loadingText || 'Processing...';
    button.innerHTML = `<span class="ux-btn-spinner" aria-hidden="true"></span><span>${label}</span>`;
    if ('disabled' in button) button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    announce(label);
    progressBar?.start();
};

const resetActionButton = (button) => {
    if (!(button instanceof HTMLElement)) return;
    button.dataset.uxActionBusy = '0';
    button.classList.remove('ux-btn-loading');
    if (button.dataset.uxOriginalHtml) {
        button.innerHTML = button.dataset.uxOriginalHtml;
    }
    if ('disabled' in button) button.disabled = false;
    button.setAttribute('aria-busy', 'false');
    progressBar?.done();
};

const bindActionLoading = (scope = document) => {
    scope.querySelectorAll('button[data-ux-action-loading], a[data-ux-link-loading]').forEach((el) => {
        if (el.dataset.uxActionBound === '1') return;
        el.dataset.uxActionBound = '1';

        el.addEventListener('click', () => {
            if (el instanceof HTMLButtonElement) {
                setActionButtonLoading(el, el.dataset.uxActionLoading);
                if ((el.type || '').toLowerCase() !== 'submit') {
                    window.setTimeout(() => resetActionButton(el), 1800);
                }
                return;
            }
            if (el instanceof HTMLAnchorElement) {
                el.classList.add('ux-btn-loading');
                if (!el.dataset.uxOriginalHtml) el.dataset.uxOriginalHtml = el.innerHTML;
                const text = el.dataset.uxLinkLoading || 'Preparing...';
                el.innerHTML = `<span class="ux-btn-spinner" aria-hidden="true"></span><span>${text}</span>`;
                el.setAttribute('aria-busy', 'true');
                announce(text);
                progressBar?.start();
                window.setTimeout(() => progressBar?.done(), 1400);
            }
        });
    });
};

const isInternalLink = (anchor) => {
    if (!anchor || anchor.target === '_blank' || anchor.hasAttribute('download')) return false;
    const href = anchor.getAttribute('href') || '';
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;

    try {
        const url = new URL(anchor.href, window.location.href);
        return url.origin === window.location.origin;
    } catch (error) {
        return false;
    }
};

const bindNavigationProgress = () => {
    if (!progressBar) return;

    document.addEventListener('click', (event) => {
        const anchor = event.target.closest('a[href]');
        if (!isInternalLink(anchor)) return;
        if (anchor.dataset.noProgress === 'true') return;
        progressBar.start();
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.noProgress === 'true') return;
        progressBar.start();
    });

    window.addEventListener('pageshow', () => progressBar.done());
    window.addEventListener('load', () => progressBar.done());
};

const installFetchProgress = () => {
    if (typeof window.fetch !== 'function') return;
    if (window.fetch.__uxWrapped) return;

    const nativeFetch = window.fetch.bind(window);
    const wrappedFetch = (input, init = {}) => {
        let shouldTrackProgress = false;
        try {
            const requestHeaders = input instanceof Request ? input.headers : undefined;
            const headers = new Headers(init.headers || requestHeaders || {});
            const isBackground = headers.get('X-UX-Background') === '1';
            shouldTrackProgress = !isBackground && headers.get('X-UX-Progress') === '1';
        } catch (error) {
            shouldTrackProgress = false;
        }

        if (shouldTrackProgress) {
            progressBar?.start();
        }

        return nativeFetch(input, init)
            .finally(() => {
                if (shouldTrackProgress) {
                    progressBar?.done();
                }
            });
    };

    wrappedFetch.__uxWrapped = true;
    window.fetch = wrappedFetch;
};

const bindSubmitFeedback = (scope = document) => {
    scope.querySelectorAll('form[data-ux-submit]').forEach((form) => {
        if (form.dataset.uxSubmitBound === '1') return;
        form.dataset.uxSubmitBound = '1';

        form.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            if (!(submitter instanceof HTMLButtonElement)) return;

            const loadingText = submitter.dataset.loadingText || 'Saving...';
            if (!submitter.dataset.originalText) {
                submitter.dataset.originalText = submitter.innerHTML;
            }
            submitter.innerHTML = loadingText;
            submitter.disabled = true;
            submitter.setAttribute('aria-busy', 'true');
            announce(loadingText);
            if (progressBar) progressBar.start();
        });
    });
};

window.BuUx = {
    progress: progressBar,
    panel: panelApi,
    announce,
    toast,
    bindSubmitFeedback,
    bindActionLoading,
    setAutoSubmitFormLoading,
    setActionButtonLoading,
    resetActionButton,
};

progressBar?.start();
installFetchProgress();

document.addEventListener('DOMContentLoaded', () => {
    finishInitialSkeleton();
    bindNavigationProgress();
    bindSubmitFeedback(document);
    bindActionLoading(document);
});

window.addEventListener('load', () => {
    finishInitialSkeleton(true);
});

window.addEventListener('pageshow', () => {
    finishInitialSkeleton(true);
});
