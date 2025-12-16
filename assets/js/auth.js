// Логіка для сторінок авторизації
class AuthManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupFormValidation();
        this.setupPhoneMask();
        this.setupErrorClearing();
    }

    setupFormValidation() {
        const forms = document.querySelectorAll('.auth-form');

        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    validateForm(form) {
        let isValid = true;
        const email = form.querySelector('input[type="email"]');
        const password = form.querySelector('input[type="password"]');
        const confirm = form.querySelector('input[name="confirm"]');
        const phone = form.querySelector('input[type="tel"]');
        const name = form.querySelector('input[name="name"]');

        // Валідація email
        if (email && !this.isValidEmail(email.value)) {
            this.showFieldError(email, 'Введіть коректний email');
            isValid = false;
        }

        // Валідація пароля
        if (password && !this.isValidPassword(password.value)) {
            this.showFieldError(password, 'Пароль повинен містити щонайменше 6 символів');
            isValid = false;
        }

        // Перевірка підтвердження пароля
        if (confirm && password && confirm.value !== password.value) {
            this.showFieldError(confirm, 'Паролі не співпадають');
            isValid = false;
        }

        // Валідація телефону
        if (phone && !this.isValidPhone(phone.value)) {
            this.showFieldError(phone, 'Введіть коректний номер телефону');
            isValid = false;
        }

        // Валідація імені
        if (name && name.value.length < 2) {
            this.showFieldError(name, "Ім'я повинно містити щонайменше 2 символи");
            isValid = false;
        }

        return isValid;
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    isValidPassword(password) {
        return password.length >= 6;
    }

    isValidPhone(phone) {
        const phoneRegex = /^[\d\s\-\+\(\)]{10,20}$/;
        return phoneRegex.test(phone);
    }

    showFieldError(field, message) {
        field.classList.add('field-error');

        // Видаляємо попередні повідомлення про помилки
        const existingError = field.parentElement.querySelector('.field-error-text');
        if (existingError) {
            existingError.remove();
        }

        // Додаємо нове повідомлення про помилку
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error-text';
        errorDiv.textContent = message;
        field.parentElement.appendChild(errorDiv);
    }

    setupPhoneMask() {
        const phoneInput = document.getElementById('phone');
        if (!phoneInput) return;

        phoneInput.addEventListener('input', () => {
            let digits = phoneInput.value.replace(/\D/g, '');

            // якщо починають вводити з 0 → Україна
            if (digits.startsWith('0')) {
                digits = '38' + digits;
            }

            // не більше 12 цифр
            digits = digits.substring(0, 12);

            let formatted = '+';

            if (digits.length > 0) {
                formatted += digits.substring(0, 2);
            }
            if (digits.length > 2) {
                formatted += ' (' + digits.substring(2, 5);
            }
            if (digits.length >= 5) {
                formatted += ') ' + digits.substring(5, 8);
            }
            if (digits.length >= 8) {
                formatted += '-' + digits.substring(8, 10);
            }
            if (digits.length >= 10) {
                formatted += '-' + digits.substring(10, 12);
            }

            phoneInput.value = formatted;
        });
    }


    setupErrorClearing() {
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => {
                if (input.classList.contains('field-error')) {
                    input.classList.remove('field-error');
                    const errorText = input.parentElement.querySelector('.field-error-text');
                    if (errorText) {
                        errorText.remove();
                    }
                }
            });
        });
    }
}

// Ініціалізація на сторінках авторизації
if (document.body.classList.contains('auth-page')) {
    document.addEventListener('DOMContentLoaded', function() {
        window.authManager = new AuthManager();
    });
}