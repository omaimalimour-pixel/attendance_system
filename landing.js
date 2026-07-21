/* ============================================
   CHRONOX — LANDING INTERACTIONS + 3D SCENE
   ============================================ */

// ===== NAV =====
const nav = document.getElementById('nav');
const navLinks = document.getElementById('navLinks');
const navBurger = document.getElementById('navBurger');

window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 30);
});
if (navBurger) {
    navBurger.addEventListener('click', () => navLinks.classList.toggle('open'));
    navLinks.querySelectorAll('a').forEach(a =>
        a.addEventListener('click', () => navLinks.classList.remove('open')));
}

// ===== SCROLL REVEAL =====
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((e, i) => {
        if (e.isIntersecting) {
            setTimeout(() => e.target.classList.add('in'), (i % 4) * 90);
            revealObserver.unobserve(e.target);
        }
    });
}, { threshold: 0.15 });
document.querySelectorAll('[data-reveal]').forEach(el => revealObserver.observe(el));

// ===== BROWSER 3D IN-VIEW =====
const browser = document.getElementById('browser3d');
if (browser) {
    const bObs = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) browser.classList.add('in'); });
    }, { threshold: 0.3 });
    bObs.observe(browser);
}

// ===== COUNT UP =====
const countObserver = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (!e.isIntersecting) return;
        const el = e.target;
        const target = parseFloat(el.dataset.count);
        const suffix = el.dataset.suffix || '';
        const isFloat = target % 1 !== 0;
        let cur = 0;
        const dur = 1500, start = performance.now();
        function tick(now) {
            const p = Math.min((now - start) / dur, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            cur = target * eased;
            el.textContent = (isFloat ? cur.toFixed(1) : Math.floor(cur)) + suffix;
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = (isFloat ? target.toFixed(1) : target) + suffix;
        }
        requestAnimationFrame(tick);
        countObserver.unobserve(el);
    });
}, { threshold: 0.5 });
document.querySelectorAll('[data-count]').forEach(el => countObserver.observe(el));


// ===== 3D TILT CARDS =====
document.querySelectorAll('.tilt').forEach(card => {
    card.addEventListener('mousemove', (e) => {
        const r = card.getBoundingClientRect();
        const px = (e.clientX - r.left) / r.width - 0.5;
        const py = (e.clientY - r.top) / r.height - 0.5;
        card.style.transform =
            `perspective(900px) rotateY(${px * 10}deg) rotateX(${-py * 10}deg) translateY(-6px)`;
    });
    card.addEventListener('mouseleave', () => { card.style.transform = ''; });
});

// ===== THREE.JS HERO SCENE =====
(function initThree() {
    if (typeof THREE === 'undefined') return;
    const canvas = document.getElementById('heroCanvas');
    if (!canvas) return;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 100);
    camera.position.z = 15;

    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // --- Wireframe icosahedron (the "core") ---
    const geo = new THREE.IcosahedronGeometry(3.4, 1);
    const wire = new THREE.WireframeGeometry(geo);
    const lineMat = new THREE.LineBasicMaterial({ color: 0x818cf8, transparent: true, opacity: 0.55 });
    const core = new THREE.LineSegments(wire, lineMat);
    scene.add(core);

    // --- Inner glowing sphere ---
    const innerGeo = new THREE.IcosahedronGeometry(2.2, 2);
    const innerMat = new THREE.MeshBasicMaterial({ color: 0x22d3ee, transparent: true, opacity: 0.08, wireframe: true });
    const inner = new THREE.Mesh(innerGeo, innerMat);
    scene.add(inner);

    // --- Particle field ---
    const pCount = 900;
    const positions = new Float32Array(pCount * 3);
    const colorChoices = [new THREE.Color(0x818cf8), new THREE.Color(0x22d3ee), new THREE.Color(0xa78bfa)];
    const pColors = new Float32Array(pCount * 3);
    for (let i = 0; i < pCount; i++) {
        const radius = 8 + Math.random() * 24;
        const theta = Math.random() * Math.PI * 2;
        const phi = Math.acos(2 * Math.random() - 1);
        positions[i*3]   = radius * Math.sin(phi) * Math.cos(theta);
        positions[i*3+1] = radius * Math.sin(phi) * Math.sin(theta);
        positions[i*3+2] = radius * Math.cos(phi);
        const c = colorChoices[i % 3];
        pColors[i*3] = c.r; pColors[i*3+1] = c.g; pColors[i*3+2] = c.b;
    }
    const pGeo = new THREE.BufferGeometry();
    pGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    pGeo.setAttribute('color', new THREE.BufferAttribute(pColors, 3));
    const pMat = new THREE.PointsMaterial({ size: 0.09, vertexColors: true, transparent: true, opacity: 0.85 });
    const particles = new THREE.Points(pGeo, pMat);
    scene.add(particles);

    // --- Mouse parallax ---
    let mouseX = 0, mouseY = 0, targetX = 0, targetY = 0;
    window.addEventListener('mousemove', (e) => {
        mouseX = (e.clientX / window.innerWidth - 0.5);
        mouseY = (e.clientY / window.innerHeight - 0.5);
    });

    let scrollY = 0;
    window.addEventListener('scroll', () => { scrollY = window.scrollY; });

    const clock = new THREE.Clock();
    function animate() {
        const t = clock.getElapsedTime();
        core.rotation.y = t * 0.15;
        core.rotation.x = t * 0.08;
        inner.rotation.y = -t * 0.22;
        inner.rotation.z = t * 0.1;
        particles.rotation.y = t * 0.03;

        targetX += (mouseX - targetX) * 0.05;
        targetY += (mouseY - targetY) * 0.05;
        camera.position.x = targetX * 4;
        camera.position.y = -targetY * 4 - scrollY * 0.004;
        camera.lookAt(0, 0, 0);

        const pulse = 1 + Math.sin(t * 1.4) * 0.04;
        core.scale.setScalar(pulse);

        renderer.render(scene, camera);
        requestAnimationFrame(animate);
    }
    animate();

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
})();
