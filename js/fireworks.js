import show from './src/forum/fireworks';

// A standalone script, added to the page by the server only on fireworks days.
window.FestoonFireworks = show;
document.dispatchEvent(new Event('festoon:fireworks'));
