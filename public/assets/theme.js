// Highlights the current page link in the top nav and the dashboard sidebars.
function markActiveLinks() {
    var current = window.location.pathname.replace(/\/+$/, '').toLowerCase() || '/';

    document.querySelectorAll('.navlinks a, .sidebar a').forEach(function (link) {
        var url = new URL(link.href, window.location.origin);

        if (url.origin !== window.location.origin || url.hash) {
            link.classList.remove('is-active');
            return;
        }

        var path = url.pathname.replace(/\/+$/, '').toLowerCase() || '/';
        var isActive = path === '/'
            ? current === '/'
            : current === path || current.indexOf(path + '/') === 0;

        link.classList.toggle('is-active', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
        }
    });
}

// Booking picker: show only the time list of the chosen day.
// Hidden lists are disabled so only one "slot" value is sent.
document.addEventListener('change', function (event) {
    var daySelect = event.target.closest('[data-slot-day]');

    if (!daySelect) {
        return;
    }

    var picker = daySelect.closest('[data-slot-picker]');

    picker.querySelectorAll('[data-slot-time]').forEach(function (timeSelect) {
        var isChosenDay = timeSelect.dataset.slotTime === daySelect.value;

        timeSelect.hidden = !isChosenDay;
        timeSelect.disabled = !isChosenDay;

        if (isChosenDay) {
            picker.querySelector('label[for^="slot-time"]').htmlFor = timeSelect.id;
        } else {
            timeSelect.value = '';
        }
    });
});

// Home hero slider: fades between images every 5 seconds, dots to jump, pauses on hover.
var heroTimer = null;

function startHeroSlider() {
    clearInterval(heroTimer);

    var slider = document.querySelector('[data-hero-slider]');

    if (!slider || slider.dataset.ready) {
        return;
    }

    slider.dataset.ready = '1';

    var slides = slider.querySelectorAll('.hero-slide');
    var dots = slider.querySelectorAll('[data-hero-dot]');
    var label = slider.querySelector('[data-hero-label]');
    var current = 0;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function show(index) {
        current = (index + slides.length) % slides.length;

        slides.forEach(function (slide, i) {
            slide.classList.toggle('is-active', i === current);
        });

        if (label) {
            label.textContent = slides[current].dataset.label;
        }

        dots.forEach(function (dot, i) {
            dot.classList.toggle('is-active', i === current);
        });
    }

    function play() {
        clearInterval(heroTimer);

        if (!reduceMotion) {
            heroTimer = setInterval(function () {
                show(current + 1);
            }, 5000);
        }
    }

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            show(Number(dot.dataset.heroDot));
            play();
        });
    });

    // Pause only while the user is using the dots, not while reading the text.
    var controls = slider.querySelector('.hero-controls') || slider;

    controls.addEventListener('mouseenter', function () {
        clearInterval(heroTimer);
    });

    controls.addEventListener('mouseleave', play);

    play();
}

document.addEventListener('DOMContentLoaded', startHeroSlider);
document.addEventListener('turbo:load', startHeroSlider);
document.addEventListener('turbo:before-render', function () {
    clearInterval(heroTimer);
});

// Real height of the sticky top bar (it can wrap or change with zoom),
// used by the fixed admin sidebar so it always starts right under it.
function setTopbarHeight() {
    var topbar = document.querySelector('.topbar');

    if (topbar) {
        document.documentElement.style.setProperty('--topbar-h', topbar.getBoundingClientRect().height + 'px');
    }
}

document.addEventListener('DOMContentLoaded', setTopbarHeight);
document.addEventListener('turbo:load', setTopbarHeight);
window.addEventListener('resize', setTopbarHeight);

// Admin sidebar always stays between the header and the footer:
// when the footer scrolls into view the sidebar gets shorter (its links scroll inside it)
// instead of being pushed up under the header.
var sidebarFrame = null;

function fitAdminSidebar() {
    sidebarFrame = null;

    var sidebar = document.querySelector('.admin-sidebar');

    if (!sidebar || getComputedStyle(sidebar).position !== 'sticky') {
        return;
    }

    var gap = 16;
    var topbar = document.querySelector('.topbar');
    var footer = document.querySelector('.footer');
    var top = (topbar ? topbar.getBoundingClientRect().height : 73) + gap;
    var bottom = window.innerHeight - gap;

    if (footer) {
        bottom = Math.min(bottom, footer.getBoundingClientRect().top - gap);
    }

    sidebar.style.height = Math.max(180, bottom - top) + 'px';
}

function requestFitAdminSidebar() {
    if (!sidebarFrame) {
        sidebarFrame = window.requestAnimationFrame(fitAdminSidebar);
    }
}

window.addEventListener('scroll', requestFitAdminSidebar, { passive: true });
window.addEventListener('resize', requestFitAdminSidebar);
document.addEventListener('DOMContentLoaded', fitAdminSidebar);
document.addEventListener('turbo:load', fitAdminSidebar);

// Keep the active admin link visible inside the sidebar when the list is long.
function revealActiveSidebarLink() {
    markActiveLinks();

    var sidebar = document.querySelector('.admin-sidebar');
    var active = sidebar && sidebar.querySelector('a.is-active');

    // Scroll only the sidebar (not the page) when the active link is below its visible area.
    if (active && active.offsetTop + active.offsetHeight > sidebar.clientHeight) {
        sidebar.scrollTop = active.offsetTop - sidebar.clientHeight / 2;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    markActiveLinks();
    revealActiveSidebarLink();
});
document.addEventListener('turbo:load', revealActiveSidebarLink);

document.addEventListener('DOMContentLoaded', markActiveLinks);
document.addEventListener('turbo:load', markActiveLinks);
