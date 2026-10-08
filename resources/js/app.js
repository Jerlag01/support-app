document.documentElement.classList.add('js');

// Mobile navigation toggle
const navToggle = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');

navToggle?.addEventListener('click', () => {
    const open = navToggle.getAttribute('aria-expanded') === 'true';
    navToggle.setAttribute('aria-expanded', String(!open));
    navMenu.classList.toggle('hidden', open);
});

// Header shadow once the page is scrolled
const header = document.querySelector('[data-header]');
const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 20);
updateHeader();
window.addEventListener('scroll', updateHeader, { passive: true });

// Reveal elements as they enter the viewport
const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.15 },
);

document.querySelectorAll('[data-reveal]').forEach((el) => observer.observe(el));

// Show the names of the files picked in an upload field
document.querySelectorAll('[data-file-input]').forEach((input) => {
    const list = document.querySelector(`[data-file-list="${input.id}"]`);
    input.addEventListener('change', () => {
        list.replaceChildren(
            ...[...input.files].map((file) => {
                const item = document.createElement('li');
                item.textContent = `${file.name} (${Math.ceil(file.size / 1024)} KB)`;
                return item;
            }),
        );
    });
});

// Prevent double submits on slow uploads
document.querySelectorAll('form[data-submit-once]').forEach((form) => {
    form.addEventListener('submit', () => {
        form.querySelectorAll('button[type="submit"]').forEach((button) => (button.disabled = true));
    });
});
