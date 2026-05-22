
import Alpine from 'alpinejs';
import registerPlayback from './playback.js';

window.Alpine = Alpine;

registerPlayback(Alpine);

Alpine.start();
