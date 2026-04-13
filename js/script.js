document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('#registerForm, #loginForm, form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            let errors = [];

            if (form.id === 'registerForm') {
                const username = form.querySelector('#username');
                const email = form.querySelector('#email');
                const password = form.querySelector('#password');

                if (username.value.length < 3) {
                    errors.push('Username must be at least 3 characters long.');
                }
                if (!email.value.includes('@')) {
                    errors.push('Please enter a valid email address.');
                }
                if (password.value.length < 6) {
                    errors.push('Password must be at least 6 characters long.');
                }
            }

            if (form.id === 'loginForm') {
                const email = form.querySelector('#email');
                const password = form.querySelector('#password');

                if (!email.value.includes('@')) {
                    errors.push('Please enter a valid email address.');
                }
                if (!password.value) {
                    errors.push('Password is required.');
                }
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    });
});