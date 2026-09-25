(function () {
    'use strict';

    var TOAST_DURATION = 4500;

    var TOAST_META = {
        success: { title: 'Success', icon: 'ti-check' },
        error:   { title: 'Error',   icon: 'ti-x' },
        warning: { title: 'Warning', icon: 'ti-alert-triangle' },
        info:    { title: 'Notice',  icon: 'ti-info-circle' }
    };

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initVex() {
        if (typeof vex === 'undefined') {
            return;
        }
        vex.defaultOptions.className = 'vex-theme-agri';
        vex.dialog.buttons.YES.text = 'Confirm';
        vex.dialog.buttons.NO.text = 'Cancel';
    }

    function toast(type, message) {
        var meta = TOAST_META[type] || TOAST_META.info;
        var safeMessage = escapeHtml(message);

        vex.open({
            className: 'vex-theme-agri vex-agri-toast vex-agri-toast--' + type,
            showCloseButton: false,
            escapeButtonCloses: true,
            overlayClosesOnClick: true,
            unsafeContent:
                '<div class="vex-agri-toast__card">' +
                    '<div class="vex-agri-toast__icon" aria-hidden="true">' +
                        '<i class="ti ' + meta.icon + '"></i>' +
                    '</div>' +
                    '<div class="vex-agri-toast__body">' +
                        '<div class="vex-agri-toast__title">' + meta.title + '</div>' +
                        '<div class="vex-agri-toast__text">' + safeMessage + '</div>' +
                    '</div>' +
                    '<div class="vex-agri-toast__progress" aria-hidden="true"></div>' +
                '</div>',
            afterOpen: function () {
                document.body.classList.remove('vex-open');
                document.body.style.overflow = '';

                var overlays = document.querySelectorAll('.vex-overlay');
                var overlay = overlays[overlays.length - 1];
                if (overlay) {
                    overlay.classList.add('vex-agri-toast-overlay');
                }

                var instance = this;
                window.setTimeout(function () {
                    vex.close(instance);
                }, TOAST_DURATION);
            }
        });
    }

    function confirmHtml(variant, message) {
        var isDanger = variant === 'danger';
        var icon = isDanger ? 'ti-trash' : 'ti-help-circle';
        var title = isDanger ? 'Are you sure?' : 'Please confirm';

        return (
            '<div class="vex-agri-confirm">' +
                '<div class="vex-agri-confirm__icon' + (isDanger ? ' vex-agri-confirm__icon--danger' : '') + '">' +
                    '<i class="ti ' + icon + '"></i>' +
                '</div>' +
                '<h2 class="vex-agri-confirm__title">' + title + '</h2>' +
                '<p class="vex-agri-confirm__text">' + escapeHtml(message) + '</p>' +
            '</div>'
        );
    }

    function showConfirm(message, variant, onResult) {
        var className = 'vex-theme-agri vex-agri-confirm';
        if (variant === 'danger') {
            className += ' vex-agri-confirm--danger';
        }

        vex.dialog.confirm({
            className: className,
            unsafeMessage: confirmHtml(variant, message),
            callback: onResult
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var msg = form.getAttribute('data-vex-confirm');
        if (!msg) {
            return;
        }

        if (form.dataset.vexConfirmed === '1') {
            delete form.dataset.vexConfirmed;
            return;
        }

        e.preventDefault();

        var variant = form.getAttribute('data-vex-confirm-variant') || 'default';

        showConfirm(msg, variant, function (confirmed) {
            if (!confirmed) {
                return;
            }
            form.dataset.vexConfirmed = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });

    initVex();

    if (window.__AGRI_FLASH__) {
        var flash = window.__AGRI_FLASH__;
        delete window.__AGRI_FLASH__;
        toast(flash.type || 'info', flash.message || '');
    }

    window.AgriVex = { toast: toast, confirm: showConfirm };
})();
