<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <script>
            document.documentElement.setAttribute('data-ux-boot-loading', '1');
            document.documentElement.setAttribute('data-ux-boot-start', String(Date.now()));
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- ✅ AlpineJS (load ONCE globally) -->
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
        <style>[x-cloak]{display:none !important;}</style>
    </head>
    <body class="font-sans antialiased">
        <x-ui.feedback-shell />
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <script>
            function isFieldFilled(el) {
                if (!el || el.disabled || el.type === 'hidden') return false;
                if (el.type === 'checkbox' || el.type === 'radio') return el.checked;
                if (el.tagName === 'SELECT') {
                    if (el.multiple) return el.selectedOptions.length > 0;
                    return el.value !== '';
                }
                return (el.value || '').trim() !== '';
            }

            function validateFormRows(form) {
                if (!form || form.dataset.viewOnly === 'true') return true;
                const evidenceSelects = form.querySelectorAll('select[name*="[evidence]"]');
                let invalid = false;
                let firstInvalid = null;

                evidenceSelects.forEach((select) => {
                    const row = select.closest('tr')
                        || select.closest('[data-evidence-row]')
                        || select.closest('.evidence-row')
                        || select.closest('div')
                        || form;

                    const fields = Array.from(row.querySelectorAll('input,select,textarea'))
                        .filter((el) => {
                            if (el.type === 'hidden' || el.disabled) return false;
                            if (el.closest('[data-skip-row-validation]')) return false;
                            const name = String(el.getAttribute('name') || '').trim();
                            return name !== '';
                        });

                    const nonEvidenceFields = fields.filter(el => el !== select);
                    const proxy = row.querySelector('[data-evidence-proxy]');

                    const hiddenEvidenceInputs = row.querySelectorAll('input[type="hidden"][name*="[evidence]"]');
                    const hasHiddenEvidence = hiddenEvidenceInputs.length > 0;
                    const hasSelectEvidence = isFieldFilled(select);
                    const started = nonEvidenceFields.some(isFieldFilled) || hasSelectEvidence || hasHiddenEvidence;
                    if (!started) {
                        select.classList.remove('border-red-500');
                        nonEvidenceFields.forEach(el => el.classList.remove('border-red-500'));
                        if (proxy) proxy.classList.remove('ring-1', 'ring-red-500');
                        return;
                    }

                    let rowInvalid = false;

                    // Require evidence when row is started
                    if (!hasSelectEvidence && !hasHiddenEvidence) {
                        rowInvalid = true;
                        select.classList.add('border-red-500');
                        if (proxy) proxy.classList.add('ring-1', 'ring-red-500', 'rounded-lg');
                    } else {
                        select.classList.remove('border-red-500');
                        if (proxy) proxy.classList.remove('ring-1', 'ring-red-500');
                    }

                    // Require other fields to be filled when row is started
                    nonEvidenceFields.forEach((el) => {
                        if (!isFieldFilled(el)) {
                            rowInvalid = true;
                            el.classList.add('border-red-500');
                        } else {
                            el.classList.remove('border-red-500');
                        }
                    });

                    if (rowInvalid) {
                        invalid = true;
                        if (!firstInvalid) firstInvalid = select;
                    }
                });

                if (invalid) {
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus({ preventScroll: true });
                    }
                    alert('Please complete all required fields and evidence for each started row before continuing.');
                }

                return !invalid;
            }

            window.validateFormRows = validateFormRows;

            const bindAutoSubmitFilters = (scope = document) => {
                scope.querySelectorAll('form[data-auto-submit]').forEach((form) => {
                    if (form.dataset.autoSubmitBound === '1') return;
                    form.dataset.autoSubmitBound = '1';

                    const delay = Math.max(150, Number(form.dataset.autoSubmitDelay || 450));
                    const searchDelay = Math.max(delay, Number(form.dataset.autoSubmitSearchDelay || 900));
                    const liveText = form.dataset.autoSubmitText === 'true';
                    const minSearchChars = Math.max(0, Number(form.dataset.autoSubmitMinChars || 0));
                    const method = String(form.getAttribute('method') || form.method || 'GET').toUpperCase();
                    const allowAjax = method === 'GET' && form.dataset.autoSubmitAjax !== 'false';
                    let timer = null;
                    let lastPayload = '';
                    try {
                        lastPayload = new URLSearchParams(new FormData(form)).toString();
                    } catch (error) {
                        lastPayload = '';
                    }

                    const submitNow = () => {
                        if (timer) {
                            clearTimeout(timer);
                            timer = null;
                        }
                        try {
                            const nextPayload = new URLSearchParams(new FormData(form)).toString();
                            if (nextPayload === lastPayload) return;
                            lastPayload = nextPayload;
                        } catch (error) {
                            // Fallback to normal submit when payload serialization is not available.
                        }

                        if (!allowAjax) {
                            window.BuUx?.progress?.start();
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                            return;
                        }

                        if (form.dataset.autoSubmitBusy === '1') return;
                        window.BuUx?.setAutoSubmitFormLoading?.(form, true, 'Applying filters...');

                        const targetSelector = form.dataset.autoSubmitTarget
                            || (() => {
                                const parent = form.closest('[data-auto-refresh]');
                                if (parent && parent.id) return `#${parent.id}`;
                                return '';
                            })();

                        let nextUrl;
                        try {
                            const action = form.getAttribute('action') || window.location.href;
                            nextUrl = new URL(action, window.location.origin);
                            nextUrl.search = new URLSearchParams(new FormData(form)).toString();
                        } catch (error) {
                            window.BuUx?.setAutoSubmitFormLoading?.(form, false);
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                            return;
                        }

                        if (!targetSelector) {
                            window.BuUx?.setAutoSubmitFormLoading?.(form, false);
                            window.BuUx?.progress?.start();
                            window.location.assign(nextUrl.toString());
                            return;
                        }

                        window.BuUx?.progress?.start();
                        window.BuUx?.panel?.clearError(targetSelector);
                        window.BuUx?.panel?.setLoading(targetSelector, true, 'Updating filtered results...');

                        fetch(nextUrl.toString(), {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-UX-Progress': '1',
                            },
                        })
                            .then((response) => {
                                if (!response.ok) throw new Error('Request failed');
                                return response.text();
                            })
                            .then((html) => {
                                const parsed = new DOMParser().parseFromString(html, 'text/html');
                                const incoming = parsed.querySelector(targetSelector);
                                const current = document.querySelector(targetSelector);
                                if (!incoming || !current) {
                                    window.BuUx?.progress?.start();
                                    window.location.assign(nextUrl.toString());
                                    return;
                                }
                                current.replaceWith(incoming);
                                if (history && typeof history.replaceState === 'function') {
                                    history.replaceState({}, '', nextUrl.toString());
                                }
                                bindAutoSubmitFilters(document);
                                window.BuUx?.bindSubmitFeedback?.(document);
                                window.BuUx?.bindActionLoading?.(document);
                                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                    window.Alpine.initTree(incoming);
                                }
                                window.BuUx?.announce?.('Results updated.');
                            })
                            .catch(() => {
                                window.BuUx?.panel?.showError(targetSelector, 'Could not update results. Please try again.');
                                window.BuUx?.toast?.('Could not update results. Please try again.', 'error');
                            })
                            .finally(() => {
                                window.BuUx?.setAutoSubmitFormLoading?.(form, false);
                                window.BuUx?.panel?.setLoading(targetSelector, false);
                                window.BuUx?.progress?.done();
                            });
                    };

                    const submitDebounced = () => {
                        if (timer) clearTimeout(timer);
                        timer = setTimeout(() => {
                            submitNow();
                        }, delay);
                    };
                    const submitDebouncedWith = (ms) => {
                        const wait = Math.max(150, Number(ms || delay));
                        if (timer) clearTimeout(timer);
                        timer = setTimeout(() => {
                            submitNow();
                        }, wait);
                    };

                    form.addEventListener('submit', () => {
                        if (timer) {
                            clearTimeout(timer);
                            timer = null;
                        }
                    });

                    form.querySelectorAll('input, select, textarea').forEach((field) => {
                        if (field.disabled || field.type === 'hidden' || field.dataset.autoSubmitIgnore === 'true') {
                            return;
                        }

                        const tag = String(field.tagName || '').toLowerCase();
                        const type = String(field.type || '').toLowerCase();
                        const immediate = tag === 'select'
                            || type === 'checkbox'
                            || type === 'radio'
                            || type === 'date'
                            || type === 'datetime-local'
                            || type === 'month'
                            || type === 'week'
                            || type === 'time';

                        if (immediate) {
                            field.addEventListener('change', submitNow);
                            return;
                        }

                        const fieldName = String(field.name || '').toLowerCase();
                        const isSearchField = field.dataset.autoSubmitInput === 'true'
                            || field.type === 'search'
                            || fieldName === 'q'
                            || fieldName === 'search';
                        let isComposing = false;
                        field.addEventListener('compositionstart', () => { isComposing = true; });
                        field.addEventListener('compositionend', () => { isComposing = false; });

                        // By default, avoid auto-submit while typing.
                        // Text inputs submit on change/blur (or Enter) unless explicitly enabled.
                        if (liveText || isSearchField) {
                            field.addEventListener('input', () => {
                                if (isComposing) return;
                                if (isSearchField) {
                                    const valueLength = String(field.value || '').trim().length;
                                    if (valueLength > 0 && valueLength < minSearchChars) {
                                        return;
                                    }
                                    submitDebouncedWith(searchDelay);
                                    return;
                                }
                                submitDebouncedWith(delay);
                            });
                        }
                        field.addEventListener('change', submitNow);
                        field.addEventListener('keydown', (event) => {
                            if (tag === 'textarea') return;
                            if (event.key !== 'Enter') return;
                            event.preventDefault();
                            submitNow();
                        });
                    });
                });
            };

            const initAutoRefreshPanels = () => {
                window.__autoRefreshTimers = window.__autoRefreshTimers || {};
                const textHash = (value) => {
                    const str = String(value || '');
                    let hash = 0;
                    for (let i = 0; i < str.length; i++) {
                        hash = ((hash << 5) - hash) + str.charCodeAt(i);
                        hash |= 0;
                    }
                    return String(hash);
                };

                document.querySelectorAll('[data-auto-refresh]').forEach((panel) => {
                    const selector = panel.id ? `#${panel.id}` : (panel.dataset.autoRefreshTarget || '');
                    if (!selector) return;

                    const url = panel.dataset.autoRefreshUrl || window.location.href;
                    const intervalMs = Math.max(3000, Number(panel.dataset.autoRefreshInterval || 10000));
                    const pauseOnFocus = panel.dataset.autoRefreshPauseOnFocus !== 'false';
                    const timerKey = `${selector}|${url}`;
                    if (!panel.dataset.autoRefreshHash) {
                        panel.dataset.autoRefreshHash = textHash(panel.innerHTML);
                    }

                    if (window.__autoRefreshTimers[timerKey]) {
                        return;
                    }

                    const tick = async () => {
                        if (document.hidden) return;

                        const current = document.querySelector(selector);
                        if (!current) return;

                        if (pauseOnFocus) {
                            const active = document.activeElement;
                            if (
                                active
                                && current.contains(active)
                                && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName)
                            ) {
                                return;
                            }
                        }

                        if (document.querySelector('form[data-async-action][data-async-busy="1"]')) {
                            return;
                        }

                        window.BuUx?.panel?.setRefreshing(selector, true);
                        try {
                            const response = await fetch(url, {
                                method: 'GET',
                                credentials: 'same-origin',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-UX-Background': '1',
                                },
                            });
                            if (!response.ok) return;

                            const html = await response.text();
                            const parsed = new DOMParser().parseFromString(html, 'text/html');
                            const incoming = parsed.querySelector(selector);
                            const target = document.querySelector(selector);
                            if (!incoming || !target) return;

                            const incomingHash = textHash(incoming.innerHTML);
                            const currentHash = target.dataset.autoRefreshHash || textHash(target.innerHTML);
                            if (incomingHash === currentHash) {
                                return;
                            }

                            target.replaceWith(incoming);
                            incoming.dataset.autoRefreshHash = incomingHash;
                            bindAutoSubmitFilters(document);
                            window.BuUx?.bindSubmitFeedback?.(document);
                            window.BuUx?.bindActionLoading?.(document);
                            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                window.Alpine.initTree(incoming);
                            }
                            window.BuUx?.announce?.('Background data refreshed.');
                        } catch (error) {
                            window.BuUx?.toast?.('Background refresh failed. Retrying automatically.', 'error', 2400);
                        } finally {
                            window.BuUx?.panel?.setRefreshing(selector, false);
                        }
                    };

                    window.__autoRefreshTimers[timerKey] = window.setInterval(tick, intervalMs);
                });
            };

            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('form[data-validate-evidence]').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        const submitter = event.submitter || document.activeElement;
                        if (submitter) {
                            const skip = submitter.getAttribute('data-skip-validate') === 'true';
                            const isDraft = submitter.name === 'action' && submitter.value === 'draft';
                            if (skip || isDraft) return;
                        }
                        if (!validateFormRows(form)) {
                            event.preventDefault();
                        }
                    });
                });

                document.addEventListener('click', (event) => {
                    if (event.defaultPrevented) return;
                    const navLink = event.target.closest('a[data-section-nav]');
                    if (!navLink) return;

                    const activePane = document.querySelector('[data-section-pane].is-active');
                    const form = activePane ? activePane.querySelector('form[data-validate-evidence]') : null;
                    if (!form) return;

                    if (!validateFormRows(form)) {
                        event.preventDefault();
                    }
                });

                bindAutoSubmitFilters(document);
                initAutoRefreshPanels();
                window.BuUx?.bindActionLoading?.(document);
            });
        </script>
    </body>
</html>
