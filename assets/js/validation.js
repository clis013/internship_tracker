document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    // Helper for displaying validation feedback
    function setError(input, message) {
        input.classList.add('is-invalid');
        
        // Find or create invalid-feedback element
        let feedback = null;
        const parent = input.parentNode;
        
        if (parent.classList.contains('input-group')) {
            // If inside an input-group, append feedback after the group or inside it
            feedback = parent.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                parent.appendChild(feedback);
            }
        } else {
            feedback = parent.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                parent.appendChild(feedback);
            }
        }
        feedback.textContent = message;
    }

    function clearError(input) {
        input.classList.remove('is-invalid');
        const parent = input.parentNode;
        const feedback = parent.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = '';
        }
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Attach listeners for interactive clearing of errors
    const inputsToWatch = document.querySelectorAll('input, select, textarea');
    inputsToWatch.forEach(input => {
        input.addEventListener('input', function() {
            clearError(input);
        });
        input.addEventListener('change', function() {
            clearError(input);
        });
    });

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            const emailInput = loginForm.querySelector('input[name="email"]');
            const passwordInput = loginForm.querySelector('input[name="password"]');

            if (emailInput) {
                clearError(emailInput);
                const emailVal = emailInput.value.trim();
                if (!emailVal) {
                    setError(emailInput, 'Email field cannot be empty.');
                    isValid = false;
                } else if (!validateEmail(emailVal)) {
                    setError(emailInput, 'Please enter a valid email address.');
                    isValid = false;
                }
            }

            if (passwordInput) {
                clearError(passwordInput);
                if (!passwordInput.value) {
                    setError(passwordInput, 'Password field cannot be empty.');
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            let isValid = true;
            const nameInput = registerForm.querySelector('input[name="name"]');
            const emailInput = registerForm.querySelector('input[name="email"]');
            const passwordInput = registerForm.querySelector('input[name="password"]');
            const confirmInput = registerForm.querySelector('input[name="confirm_password"]');
            const roleSelect = registerForm.querySelector('select[name="role"]');

            if (nameInput) {
                clearError(nameInput);
                if (!nameInput.value.trim()) {
                    setError(nameInput, 'Full name field cannot be empty.');
                    isValid = false;
                }
            }

            if (emailInput) {
                clearError(emailInput);
                const emailVal = emailInput.value.trim();
                if (!emailVal) {
                    setError(emailInput, 'Email field cannot be empty.');
                    isValid = false;
                } else if (!validateEmail(emailVal)) {
                    setError(emailInput, 'Please enter a valid email address.');
                    isValid = false;
                }
            }

            if (passwordInput) {
                clearError(passwordInput);
                if (!passwordInput.value) {
                    setError(passwordInput, 'Password field cannot be empty.');
                    isValid = false;
                } else if (passwordInput.value.length < 6) {
                    setError(passwordInput, 'Password must be at least 6 characters.');
                    isValid = false;
                }
            }

            if (confirmInput && passwordInput) {
                clearError(confirmInput);
                if (!confirmInput.value) {
                    setError(confirmInput, 'Please confirm your password.');
                    isValid = false;
                } else if (passwordInput.value !== confirmInput.value) {
                    setError(confirmInput, 'Passwords do not match.');
                    isValid = false;
                }
            }

            if (roleSelect) {
                clearError(roleSelect);
                if (!roleSelect.value) {
                    setError(roleSelect, 'Please select your role.');
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }
});
