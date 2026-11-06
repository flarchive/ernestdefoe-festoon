import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import WelcomeHero from 'flarum/forum/components/WelcomeHero';
import FieldSet from 'flarum/common/components/FieldSet';
import Switch from 'flarum/common/components/Switch';

declare const m: import('mithril').Static;

interface Payload {
  look?: string;
  preview?: boolean;
  name?: string;
  tip?: boolean;
  sky?: string[];
}

const PREF = 'festoonDecorations';
const root = document.documentElement;
const stillPlease = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

app.initializers.add('ernestdefoe-festoon', () => {
  const data = (app.data as { festoon?: Payload }).festoon;

  // A member's switch. Turning it off hides everything at once (the decoration
  // stylesheet hangs off html:not(.Festoon-off)); turning it back on reloads,
  // because the server left the decorations out of this page for them.
  // SettingsPage is split into its own chunk in Flarum 2, so it is extended by
  // module path; importing it would load it on every page.
  extend('flarum/forum/components/SettingsPage', 'settingsItems', function (this: any, items: any) {
    const user = this.user;
    const on = user.preferences()?.[PREF] !== false;

    items.add(
      'festoon',
      m(FieldSet, { className: 'Settings-festoon FieldSet--min', label: app.translator.trans('ernestdefoe-festoon.forum.settings.heading') }, [
        m(
          Switch,
          {
            state: on,
            onchange: (value: boolean) =>
              user.savePreferences({ [PREF]: value }).then(() => {
                if (value && root.classList.contains('Festoon-off')) return window.location.reload();
                root.classList.toggle('Festoon-off', !value);
                m.redraw();
              }),
          },
          app.translator.trans('ernestdefoe-festoon.forum.settings.decorations_label')
        ),
        m('p.helpText', app.translator.trans('ernestdefoe-festoon.forum.settings.decorations_help')),
      ]),
      5
    );
  });

  const name = data?.name || data?.look || '';

  if (data?.preview) {
    setTimeout(() => app.alerts.show({ type: 'warning' }, app.translator.trans('ernestdefoe-festoon.forum.preview', { name })));
  }

  // The piece beside the forum's name is drawn by CSS; this empty button over
  // it names the occasion on hover, keyboard focus or a tap.
  const title = document.querySelector('.App-header .Header-title');
  if (data?.tip && name && title && !title.querySelector('.Festoon-tip')) {
    const tip = document.createElement('button');
    tip.type = 'button';
    tip.className = 'Festoon-tip';
    tip.setAttribute('aria-label', name);
    title.appendChild(tip);
  }

  // Fireworks: the server adds their script only on a day that has them, so
  // this bundle carries none of it. The script may arrive after the welcome
  // banner is drawn; whichever comes second starts the show.
  const sky = data?.sky;
  if (!sky?.length) return;

  let hero: HTMLElement | null = null;
  let stop: (() => void) | null = null;
  const start = () => {
    const show = (window as any).FestoonFireworks as ((el: HTMLElement, colors: string[]) => () => void) | undefined;
    if (show && hero && !stop && hero.isConnected && !stillPlease() && !root.classList.contains('Festoon-off')) stop = show(hero, sky);
  };
  document.addEventListener('festoon:fireworks', start);

  extend(WelcomeHero.prototype, 'oncreate', function () {
    hero = this.element as HTMLElement;
    start();
  });

  extend(WelcomeHero.prototype, 'onremove', function () {
    stop?.();
    stop = hero = null;
  });
});
