(function () {
    var loader = document.getElementById('site-loader');
    var body = document.body;
    var MIN_LOADER_TIME = 800;

    function showLoader() {
        if (!loader) return;
        body.classList.add('is-loading');
        loader.classList.remove('is-hidden');
    }

    function hideLoader() {
        if (!loader) return;
        loader.classList.add('is-hidden');
        body.classList.remove('is-loading');
    }

    function runInitialLoader() {
        showLoader();

        var start = Date.now();

        window.addEventListener('load', function () {
            var elapsed = Date.now() - start;
            var remaining = MIN_LOADER_TIME - elapsed;

            if (remaining < 0) {
                remaining = 0;
            }

            setTimeout(function () {
                hideLoader();
            }, remaining);
        });
    }

    function setupPageTransitions() {
        var links = document.querySelectorAll('a.js-loader-link');

        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function (e) {
                var href = this.getAttribute('href');

                if (!href || href.charAt(0) === '#') {
                    return;
                }

                showLoader();
            });
        }

        var forms = document.querySelectorAll('form.js-loader-form');

        for (var j = 0; j < forms.length; j++) {
            forms[j].addEventListener('submit', function () {
                if (this.classList.contains('js-skip-loader')) {
                    return;
                }
                showLoader();
            });
        }

        window.addEventListener('pageshow', function () {
            showLoader();
            setTimeout(function () {
                hideLoader();
            }, MIN_LOADER_TIME);
        });
    }
	

    function setupModals() {
        var backdrop = document.getElementById('modal-backdrop');
        var loginModal = document.getElementById('login-modal');
        var registerModal = document.getElementById('register-modal');
        var openButtons = document.querySelectorAll('[data-open-modal]');
        var closeButtons = document.querySelectorAll('[data-close-modal]');
        var switchButtons = document.querySelectorAll('[data-switch-modal]');

        function closeAll() {
            if (backdrop) backdrop.classList.remove('is-visible');
            if (loginModal) loginModal.classList.remove('is-visible');
            if (registerModal) registerModal.classList.remove('is-visible');
        }
		
 

        function openModal(name) {
            closeAll();

            if (backdrop) backdrop.classList.add('is-visible');

            if (name === 'login' && loginModal) {
                loginModal.classList.add('is-visible');
            }

            if (name === 'register' && registerModal) {
                registerModal.classList.add('is-visible');
            }
        }

        for (var i = 0; i < openButtons.length; i++) {
            openButtons[i].addEventListener('click', function () {
                openModal(this.getAttribute('data-open-modal'));
            });
        }

        for (var j = 0; j < closeButtons.length; j++) {
            closeButtons[j].addEventListener('click', function () {
                closeAll();
            });
        }

        for (var k = 0; k < switchButtons.length; k++) {
            switchButtons[k].addEventListener('click', function () {
                openModal(this.getAttribute('data-switch-modal'));
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeAll();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAll();
            }
        }
);
    }
runInitialLoader();
setupPageTransitions();
setupModals();
 

document.addEventListener('DOMContentLoaded', function () {
    const card = document.getElementById('serverInfoCard');
    const toggle = document.getElementById('serverInfoToggle');

    if (!card || !toggle) return;

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = card.classList.contains('is-open');

        if (isOpen) {
            card.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        } else {
            card.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        }
    });

    document.addEventListener('click', function (e) {
        if (!card.contains(e.target)) {
            card.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
});

document.getElementById('openServerCard').addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation(); // 🔴 IMPORTANTE

    document.getElementById('serverInfoToggle').click();
});

})();