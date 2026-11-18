// Mobile menu toggle (antes existia uma versão duplicada e mais simples dentro
// do <script> inline do navbar.php — os dois listeners no mesmo clique
// brigavam entre si. Ficou só essa, que já troca o ícone e fecha ao clicar fora)
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenu = document.querySelector('.mobile-menu');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenu && navLinks) {
        mobileMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            navLinks.classList.toggle('active');
            const icon = this.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('active');
                const icon = mobileMenu.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        });

        document.addEventListener('click', function(event) {
            if (!navLinks.contains(event.target) && !mobileMenu.contains(event.target)) {
                navLinks.classList.remove('active');
                const icon = mobileMenu.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
});

// Toggle password visibility
// Obs: o register.php usa outra classe (.toggle-password-btn) de propósito,
// pra não colidir com esse listener em páginas que ainda usam .toggle-password.
document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
        const input = this.previousElementSibling;
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
});

// Real-time password strength
// Guard: só roda se a página tiver o componente .strength-bar (formulário antigo).
// O register.php usa outro sistema (.strength-seg x4) com JS próprio inline.
const senhaInput = document.getElementById('senha');
if(senhaInput) {
    senhaInput.addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.querySelector('.strength-bar');
        if (!strengthBar) return; // página não usa esse componente, sai cedo

        let strength = 0;

        // Check length
        if(password.length >= 6) strength += 33.33;

        // Check uppercase
        if(/[A-Z]/.test(password)) strength += 33.33;

        // Check number
        if(/[0-9]/.test(password)) strength += 33.34;

        strengthBar.style.width = strength + '%';

        if(strength <= 33.33) {
            strengthBar.className = 'strength-bar weak';
        } else if(strength <= 66.66) {
            strengthBar.className = 'strength-bar medium';
        } else {
            strengthBar.className = 'strength-bar strong';
        }
    });
}

// Real-time email validation
const emailInput = document.getElementById('email');
if(emailInput) {
    emailInput.addEventListener('input', function() {
        const email = this.value;
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

        if(isValid) {
            this.classList.add('valid');
            this.classList.remove('invalid');
        } else {
            this.classList.add('invalid');
            this.classList.remove('valid');
        }
    });
}

// Password match validation
const confirmSenha = document.getElementById('confirmar_senha');
if(confirmSenha && senhaInput) {
    confirmSenha.addEventListener('input', function() {
        if(this.value === senhaInput.value) {
            this.classList.add('valid');
            this.classList.remove('invalid');
        } else {
            this.classList.add('invalid');
            this.classList.remove('valid');
        }
    });
}

// Form submission loading state
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if(submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="loading"></span> Processando...';
            submitBtn.disabled = true;

            // Re-enable after 5 seconds (timeout protection)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        }
    });
});

// Smooth scroll for anchor links
// Guard: ignora âncoras placeholder (href="#") como as usadas pra abrir modais
// via JS (ex: link de "termos de uso") — sem isso, querySelector('#') quebra.
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href === '#' || href.length <= 1) return;

        e.preventDefault();
        const target = document.querySelector(href);
        if(target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Auto-hide alerts after 5 seconds
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
});

// Add animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if(entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.feature-card, .stat-card, .content-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.6s ease';
    observer.observe(el);
});