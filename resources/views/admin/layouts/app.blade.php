<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body>
    <div class="admin-shell d-flex">
        @include('admin.partials.sidebar')
        <div class="admin-main flex-grow-1">
            @include('admin.partials.topbar')
            <main class="admin-content container-fluid py-4">
                @yield('content')
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        (() => {
            const STICKY_SELECTOR = '.table-responsive, .table-responsive-horizontal';
            const SEARCHABLE_SELECTOR = 'select.form-select, select[data-searchable="true"], select.js-searchable-select';
            const STICKY_READY_ATTR = 'data-sticky-scroll-ready';
            const SELECT_READY_ATTR = 'data-searchable-ready';
            let resizeTimer = null;
            let scheduledRefresh = false;

            const stickyInstances = new WeakMap();

            const isSearchableCandidate = (select) => {
                if (!select || select.multiple || select.disabled) {
                    return false;
                }

                if (select.hasAttribute('data-no-searchable')) {
                    return false;
                }

                return select.hasAttribute('data-searchable')
                    || select.classList.contains('js-searchable-select')
                    || select.options.length >= 8;
            };

            const applySearchableSelect = (select) => {
                if (!window.$ || !$.fn.select2 || !select || select.hasAttribute(SELECT_READY_ATTR)) {
                    return;
                }

                if (!isSearchableCandidate(select)) {
                    return;
                }

                if (select.classList.contains('select2-hidden-accessible')) {
                    select.setAttribute(SELECT_READY_ATTR, 'true');
                    return;
                }

                const inModal = select.closest('.modal');
                $(select).select2({
                    width: '100%',
                    dropdownAutoWidth: true,
                    dropdownParent: inModal ? $(inModal) : $(document.body),
                    placeholder: select.getAttribute('placeholder') || 'Select an option',
                    allowClear: !select.required && !!select.querySelector('option[value=""]'),
                });

                select.setAttribute(SELECT_READY_ATTR, 'true');
            };

            const initSearchableSelects = (scope = document) => {
                if (!scope.querySelectorAll) {
                    return;
                }

                scope.querySelectorAll(SEARCHABLE_SELECTOR).forEach(applySearchableSelect);
            };

            const updateStickyMetrics = (container) => {
                const instance = stickyInstances.get(container);
                if (!instance) {
                    return;
                }

                const contentWidth = Math.max(container.scrollWidth, container.clientWidth);
                instance.track.style.width = `${contentWidth}px`;
                instance.bar.classList.toggle('is-hidden', contentWidth <= container.clientWidth + 1);
                instance.bar.scrollLeft = container.scrollLeft;
            };

            const setupStickyScrollbar = (container) => {
                if (!container || container.getAttribute(STICKY_READY_ATTR) === 'true') {
                    return;
                }

                const existing = container.nextElementSibling;
                let bar = existing && existing.classList.contains('table-responsive-sticky-bar') ? existing : null;

                if (!bar) {
                    bar = document.createElement('div');
                    bar.className = 'table-responsive-sticky-bar';

                    const track = document.createElement('div');
                    track.className = 'table-responsive-sticky-track';
                    bar.appendChild(track);

                    container.insertAdjacentElement('afterend', bar);
                }

                const track = bar.querySelector('.table-responsive-sticky-track') || (() => {
                    const fallbackTrack = document.createElement('div');
                    fallbackTrack.className = 'table-responsive-sticky-track';
                    bar.appendChild(fallbackTrack);
                    return fallbackTrack;
                })();

                const syncState = { fromContainer: false, fromBar: false };
                const onContainerScroll = () => {
                    if (syncState.fromBar) {
                        return;
                    }

                    syncState.fromContainer = true;
                    bar.scrollLeft = container.scrollLeft;
                    syncState.fromContainer = false;
                };

                const onBarScroll = () => {
                    if (syncState.fromContainer) {
                        return;
                    }

                    syncState.fromBar = true;
                    container.scrollLeft = bar.scrollLeft;
                    syncState.fromBar = false;
                };

                container.addEventListener('scroll', onContainerScroll, { passive: true });
                bar.addEventListener('scroll', onBarScroll, { passive: true });

                stickyInstances.set(container, { bar, track });
                container.setAttribute(STICKY_READY_ATTR, 'true');
                updateStickyMetrics(container);
            };

            const initStickyScrollbars = (scope = document) => {
                if (!scope.querySelectorAll) {
                    return;
                }

                scope.querySelectorAll(STICKY_SELECTOR).forEach(setupStickyScrollbar);
            };

            const scheduleGlobalStickyRefresh = () => {
                if (scheduledRefresh) {
                    return;
                }

                scheduledRefresh = true;
                window.requestAnimationFrame(() => {
                    scheduledRefresh = false;
                    document.querySelectorAll(`[${STICKY_READY_ATTR}="true"]`).forEach(updateStickyMetrics);
                });
            };

            const installScopedObserver = () => {
                const root = document.querySelector('.admin-content');
                if (!root) {
                    return;
                }

                const observer = new MutationObserver((mutations) => {
                    for (const mutation of mutations) {
                        mutation.addedNodes.forEach((node) => {
                            if (!(node instanceof Element)) {
                                return;
                            }

                            if (node.matches?.(STICKY_SELECTOR)) {
                                setupStickyScrollbar(node);
                            }

                            if (node.matches?.(SEARCHABLE_SELECTOR)) {
                                applySearchableSelect(node);
                            }

                            initStickyScrollbars(node);
                            initSearchableSelects(node);
                        });
                    }

                    scheduleGlobalStickyRefresh();
                });

                observer.observe(root, { childList: true, subtree: true });
            };

            document.addEventListener('DOMContentLoaded', () => {
                initStickyScrollbars(document);
                initSearchableSelects(document);
                scheduleGlobalStickyRefresh();
                installScopedObserver();

                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(scheduleGlobalStickyRefresh, 120);
                }, { passive: true });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
