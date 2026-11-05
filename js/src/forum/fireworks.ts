/*
 * Fireworks over the welcome banner.
 *
 * Rockets climb with a fading trail and burst at the top of their arc. Sparks
 * fall under gravity with air drag and flicker as they die; bursts come as
 * peonies, rings, slow gold willows and crackles. They are drawn with additive
 * blending on the banner's night sky, so where sparks cross they glow brighter,
 * the way real ones do.
 *
 * The show runs only while the banner is on screen and the tab is visible,
 * and the canvas is capped at 2× pixel density.
 */

interface Rocket {
  x: number;
  y: number;
  vx: number;
  vy: number;
  trail: [number, number][];
  color: string;
  kind: 'peony' | 'ring' | 'willow' | 'crackle';
}

interface Spark {
  x: number;
  y: number;
  px: number;
  py: number;
  vx: number;
  vy: number;
  drag: number;
  life: number;
  max: number;
  color: string;
  crackle: boolean;
  size: number;
  flash?: boolean;
}

const G = 0.045;
const KINDS: Rocket['kind'][] = ['peony', 'peony', 'ring', 'willow', 'crackle'];
const rand = (a: number, b: number) => a + Math.random() * (b - a);
const pick = <T>(a: T[]): T => a[Math.floor(Math.random() * a.length)];

export default function show(hero: HTMLElement, colors: string[]): () => void {
  const canvas = document.createElement('canvas');
  canvas.className = 'Festoon-sky';
  canvas.setAttribute('aria-hidden', 'true');
  hero.prepend(canvas);

  const cx = canvas.getContext('2d')!;
  let w = 0;
  let h = 0;
  let raf = 0;
  let last = 0;
  let nextLaunch = 0;
  let onScreen = true;
  let rockets: Rocket[] = [];
  let sparks: Spark[] = [];

  const fit = () => {
    const r = Math.min(2, window.devicePixelRatio || 1);
    w = canvas.clientWidth;
    h = canvas.clientHeight;
    canvas.width = w * r;
    canvas.height = h * r;
    cx.setTransform(r, 0, 0, r, 0, 0);
  };

  const launch = () => {
    const tx = rand(w * 0.12, w * 0.88);
    const ty = rand(h * 0.12, h * 0.42);
    const x = tx + rand(-40, 40);
    const y = h + 4;
    rockets.push({ x, y, vx: (tx - x) / 55, vy: -Math.sqrt(2 * G * (y - ty)), trail: [], color: pick(colors), kind: pick(KINDS) });
  };

  const burst = (r: Rocket) => {
    const n = r.kind === 'ring' ? 48 : r.kind === 'willow' ? 70 : rand(80, 110);
    const speed = r.kind === 'willow' ? 2.2 : rand(2.4, 3.4);
    const second = Math.random() < 0.35 ? pick(colors) : null;

    for (let i = 0; i < n; i++) {
      const a = r.kind === 'ring' ? (i / n) * Math.PI * 2 : Math.random() * Math.PI * 2;
      // cube root spreads sparks evenly through the sphere, not bunched at its middle
      const s = r.kind === 'ring' ? speed : speed * Math.cbrt(Math.random());
      sparks.push({
        x: r.x,
        y: r.y,
        px: r.x,
        py: r.y,
        vx: Math.cos(a) * s,
        vy: Math.sin(a) * s,
        drag: r.kind === 'willow' ? 0.955 : 0.965,
        life: 0,
        max: r.kind === 'willow' ? rand(110, 150) : rand(55, 85),
        color: r.kind === 'willow' ? '#ffc870' : second && i % 2 ? second : r.color,
        crackle: r.kind === 'crackle',
        size: r.kind === 'willow' ? 1.3 : 1.7,
      });
    }
    sparks.push({ flash: true, x: r.x, y: r.y, px: 0, py: 0, vx: 0, vy: 0, drag: 0, life: 0, max: 8, color: '#fff', crackle: false, size: 0 });
  };

  const frame = (t: number) => {
    raf = requestAnimationFrame(frame);
    const dt = Math.min(2, (t - last) / 16.67 || 1);
    last = t;

    if (t > nextLaunch) {
      launch();
      if (Math.random() < 0.3) launch();
      nextLaunch = t + rand(700, 1600);
    }

    cx.globalCompositeOperation = 'source-over';
    cx.clearRect(0, 0, w, h);
    cx.globalCompositeOperation = 'lighter';

    rockets = rockets.filter((r) => {
      r.trail.push([r.x, r.y]);
      if (r.trail.length > 10) r.trail.shift();
      r.x += r.vx * dt;
      r.y += r.vy * dt;
      r.vy += G * dt;
      cx.fillStyle = '#ffe0a0';
      r.trail.forEach(([x, y], i) => {
        cx.globalAlpha = (i / r.trail.length) * 0.6;
        cx.fillRect(x - 0.8, y - 0.8, 1.6, 1.6);
      });
      if (r.vy >= -0.3) {
        burst(r);
        return false;
      }
      return true;
    });

    sparks = sparks.filter((s) => {
      s.life += dt;
      const k = 1 - s.life / s.max;
      if (k <= 0) return false;

      if (s.flash) {
        cx.globalAlpha = k * 0.5;
        const g = cx.createRadialGradient(s.x, s.y, 0, s.x, s.y, 40);
        g.addColorStop(0, '#fff');
        g.addColorStop(1, 'rgba(255,255,255,0)');
        cx.fillStyle = g;
        cx.fillRect(s.x - 40, s.y - 40, 80, 80);
        return true;
      }

      s.px = s.x;
      s.py = s.y;
      s.vx *= Math.pow(s.drag, dt);
      s.vy = s.vy * Math.pow(s.drag, dt) + G * 0.55 * dt;
      s.x += s.vx * dt;
      s.y += s.vy * dt;

      let a = k < 0.35 ? k / 0.35 : 1;
      if (k < 0.45 && Math.random() < 0.3) a *= 0.2; // flicker as it dies

      if (s.crackle && k < 0.25) {
        cx.globalAlpha = Math.random();
        cx.fillStyle = '#fff';
        cx.fillRect(s.x + rand(-3, 3), s.y + rand(-3, 3), 1.2, 1.2);
      }

      cx.globalAlpha = a;
      cx.strokeStyle = s.color;
      cx.lineWidth = s.size;
      cx.lineCap = 'round';
      cx.beginPath();
      cx.moveTo(s.px - s.vx * 2, s.py - s.vy * 2);
      cx.lineTo(s.x, s.y);
      cx.stroke();
      return true;
    });

    cx.globalAlpha = 1;
  };

  const run = () => {
    if (raf || !onScreen || document.hidden) return;
    last = performance.now();
    raf = requestAnimationFrame(frame);
  };
  const pause = () => {
    cancelAnimationFrame(raf);
    raf = 0;
  };
  const visibility = () => (document.hidden ? pause() : run());

  const resize = new ResizeObserver(fit);
  resize.observe(canvas);
  const seen = new IntersectionObserver(([entry]) => {
    onScreen = entry.isIntersecting;
    onScreen ? run() : pause();
  });
  seen.observe(hero);
  document.addEventListener('visibilitychange', visibility);

  fit();
  run();

  return () => {
    pause();
    resize.disconnect();
    seen.disconnect();
    document.removeEventListener('visibilitychange', visibility);
    canvas.remove();
  };
}
