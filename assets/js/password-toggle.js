(() => {
    const eyeIcon = `
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
            <circle cx="12" cy="12" r="2.75"></circle>
        </svg>`;
    const eyeOffIcon = `
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="m3 3 18 18"></path>
            <path d="M10.6 6.15A10.6 10.6 0 0 1 12 6c6 0 9.5 6 9.5 6a16.7 16.7 0 0 1-2.1 2.85"></path>
            <path d="M6.25 7.65C3.85 9.45 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3.1-.5"></path>
            <path d="M10.05 10.05A2.75 2.75 0 0 0 13.95 13.95"></path>
        </svg>`;

    const enhancePasswordField = (input) => {
        if (!(input instanceof HTMLInputElement) || input.dataset.passwordToggleReady === 'true') {
            return;
        }

        input.dataset.passwordToggleReady = 'true';

        const computedStyle = window.getComputedStyle(input);
        const wrapper = document.createElement('span');
        wrapper.className = 'password-field-shell';
        wrapper.style.marginTop = computedStyle.marginTop;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-visibility-toggle';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        button.title = 'Show password';
        button.innerHTML = eyeIcon;

        input.parentNode?.insertBefore(wrapper, input);
        wrapper.append(input, button);

        button.addEventListener('click', () => {
            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
            button.title = shouldShow ? 'Hide password' : 'Show password';
            button.innerHTML = shouldShow ? eyeOffIcon : eyeIcon;
            input.focus({ preventScroll: true });
        });
    };

    const enhanceWithin = (root) => {
        if (root instanceof HTMLInputElement && root.matches('input[type="password"]')) {
            enhancePasswordField(root);
            return;
        }

        if (root instanceof Document || root instanceof Element) {
            root.querySelectorAll('input[type="password"]').forEach(enhancePasswordField);
        }
    };

    const initialize = () => {
        enhanceWithin(document);

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => enhanceWithin(node));
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
