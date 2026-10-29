import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';

declare const m: import('mithril').Static;

type Stream = (value?: string) => string;
type Look = Record<string, any> & { id: string; group: string };

interface Attrs {
  timezone: Stream;
  parts: Stream;
  looks: Stream;
  custom: Stream;
}

interface Data {
  looks: Look[];
  next: Record<string, [string, string] | null>;
  today: string | null;
  timezone: string;
  timezones: string[];
  options: Record<'garland' | 'ornament' | 'corner' | 'hat' | 'effect', string[]>;
  intl: boolean;
}

const PARTS = ['colors', 'header', 'effects', 'hats', 'ornaments', 'trims'];
const GROUPS = ['holidays', 'seasons', 'world', 'custom'];
const SLOTS = ['garland', 'ornament', 'corner', 'hat', 'effect'] as const;

const t = (key: string, params: Record<string, any> = {}) => app.translator.trans(`ernestdefoe-festoon.admin.${key}`, params);

const parse = <T>(stream: Stream, fallback: T): T => {
  try {
    const v = JSON.parse(stream() || '');
    return v && typeof v === 'object' ? v : fallback;
  } catch {
    return fallback;
  }
};

const day = (ymd: string) =>
  new Date(ymd + 'T12:00:00').toLocaleDateString(app.translator.getLocale() || undefined, { month: 'long', day: 'numeric', year: 'numeric' });

/**
 * Every look in four groups, each with its next dates, a switch, Preview and
 * Edit. Changes are kept as small overrides on the built-in defaults, so a
 * look Festoon improves in a later version still gets the improvement unless
 * the administrator changed that very field.
 */
export default class FestoonPanel extends Component<Attrs> {
  data!: Data;
  overrides!: Record<string, Record<string, any>>;
  custom!: Record<string, any>[];
  open: string | null = null;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.data = (app.data as any).festoon;
    this.overrides = parse(this.attrs.looks, {});
    this.custom = parse(this.attrs.custom, []);
  }

  /** The look as it stands now: server defaults, then unsaved edits on top. */
  look(id: string): Look {
    const base = this.data.looks.find((l) => l.id === id)!;
    if (base?.group === 'custom') return { ...base, ...this.customRow(id) };
    const o = this.overrides[id] || {};
    const date = base.date?.type === 'range' ? { ...base.date, ...(o.from ? { from: o.from } : {}), ...(o.to ? { to: o.to } : {}) } : base.date;
    return { ...base, ...o, date };
  }

  customRow(id: string) {
    const row = this.custom.find((c) => 'custom-' + c.id === id) || {};
    return { ...row, date: { type: 'range', from: row.from || '01-01', to: row.to || '01-01' }, id };
  }

  change(id: string, field: string, value: any) {
    if (id.startsWith('custom-')) {
      const row = this.custom.find((c) => 'custom-' + c.id === id);
      if (row) row[field] = value;
      this.attrs.custom(JSON.stringify(this.custom));
    } else {
      this.overrides[id] = { ...(this.overrides[id] || {}), [field]: value };
      this.attrs.looks(JSON.stringify(this.overrides));
    }
  }

  name(look: Look): string {
    return look.group === 'custom' ? look.name || '' : (app.translator.trans(`ernestdefoe-festoon.lib.looks.${look.id}`) as any);
  }

  view() {
    const parts = parse<Record<string, boolean>>(this.attrs.parts, {});
    const today = this.data.today ? this.look(this.data.today) : null;
    const allLooks = [
      ...this.data.looks.filter((l) => l.group !== 'custom'),
      ...this.custom.map((c) => ({ ...this.customRow('custom-' + c.id), group: 'custom' }) as Look),
    ];

    return m('.Festoon', [
      m('p.helpText', t('intro')),
      m('p.Festoon-today', today ? t('today', { name: this.name(today) }) : t('today_none')),
      !this.data.intl && m('.Alert.Alert--warning', t('no_intl')),

      m('.Form-group', [
        m('label', t('timezone_label')),
        m(
          'select.FormControl',
          { value: this.attrs.timezone() || this.data.timezone, onchange: (e: Event) => this.attrs.timezone((e.target as HTMLSelectElement).value) },
          this.data.timezones.map((tz) => m('option', { value: tz }, tz))
        ),
        m('p.helpText', t('timezone_help')),
      ]),

      m('h3', t('parts.heading')),
      m(
        '.Festoon-parts',
        PARTS.map((p) =>
          m('.Festoon-part', [
            m(
              Switch,
              { state: parts[p] !== false, onchange: (v: boolean) => this.attrs.parts(JSON.stringify({ ...parts, [p]: v })) },
              t(`parts.${p}`)
            ),
            m('p.helpText', t(`parts.${p}_help`)),
          ])
        )
      ),

      GROUPS.map((group) => {
        const looks = allLooks.filter((l) => l.group === group);
        return m('section.Festoon-group', [
          m('h3', t(`groups.${group}`)),
          ['seasons', 'world', 'custom'].includes(group) && m('p.helpText', t(`groups.${group}_help`)),
          m(
            'ul.Festoon-looks',
            looks.map((l) => this.row(this.look(l.id)))
          ),
          group === 'custom' && m(Button, { className: 'Button', icon: 'fas fa-plus', onclick: () => this.add() }, t('look.add')),
        ]);
      }),
    ]);
  }

  row(look: Look) {
    const next = this.data.next[look.id];
    const open = this.open === look.id;

    return m('li.Festoon-look', { key: look.id, className: look.enabled ? '' : 'is-off' }, [
      m('.Festoon-lookHead', [
        m('span.Festoon-swatch', { style: { background: `linear-gradient(135deg, ${look.primary} 50%, ${look.secondary} 50%)` } }),
        m('.Festoon-lookName', [
          m('strong', this.name(look) || '—'),
          this.data.today === look.id && m('span.Festoon-now', t('look.on_now')),
          m('small', next ? t('look.next', { start: day(next[0]), end: day(next[1]) }) : t('look.next_none')),
        ]),
        m(Switch, { state: !!look.enabled, onchange: (v: boolean) => this.change(look.id, 'enabled', v) }),
        m(
          Button,
          {
            className: 'Button Button--link',
            icon: 'fas fa-eye',
            onclick: () => window.open(app.forum.attribute('baseUrl') + '/?festoon=' + encodeURIComponent(look.id), '_blank'),
          },
          t('look.preview')
        ),
        m(Button, { className: 'Button Button--link', icon: 'fas fa-pen', onclick: () => (this.open = open ? null : look.id) }, t('look.edit')),
      ]),
      open && this.editor(look),
    ]);
  }

  editor(look: Look) {
    const id = look.id;
    const set = (field: string) => (e: Event) => this.change(id, field, (e.target as HTMLInputElement).value);
    const num = (field: string) => (e: Event) => this.change(id, field, parseInt((e.target as HTMLInputElement).value, 10) || 0);
    const isRange = look.date?.type === 'range';
    const trim = look.trim;
    const trimKind = trim === 'snow' ? 'snow' : Array.isArray(trim) || trim?.stripe ? 'stripe' : 'none';
    const stripes: string[] = Array.isArray(trim) ? trim : trim?.stripe || [look.primary, look.secondary];

    return m('.Festoon-editor', [
      look.group === 'custom' &&
        this.field(t('edit.name_label'), m('input.FormControl', { value: look.name || '', maxlength: 60, oninput: set('name') })),

      isRange
        ? m('.Festoon-row', [
            this.field(
              t('edit.from_label'),
              this.monthDay(look.date.from, (v) => this.change(id, 'from', v))
            ),
            this.field(
              t('edit.to_label'),
              this.monthDay(look.date.to, (v) => this.change(id, 'to', v))
            ),
          ])
        : m('.Festoon-row', [
            this.field(t('edit.before_label'), m('input.FormControl[type=number][min=0][max=60]', { value: look.before, oninput: num('before') })),
            this.field(t('edit.after_label'), m('input.FormControl[type=number][min=0][max=60]', { value: look.after, oninput: num('after') })),
          ]),
      m('p.helpText', isRange ? t('edit.dates_help') : t('edit.window_help')),

      m('.Festoon-row', [
        this.field(t('edit.primary_label'), m('input.FormControl[type=color]', { value: look.primary, oninput: set('primary') })),
        this.field(t('edit.secondary_label'), m('input.FormControl[type=color]', { value: look.secondary, oninput: set('secondary') })),
        this.field(t('edit.priority_label'), m('input.FormControl[type=number][min=0][max=100]', { value: look.priority, oninput: num('priority') })),
      ]),
      m('p.helpText', t('edit.priority_help')),

      m(
        '.Festoon-row',
        SLOTS.map((slot) =>
          this.field(
            t(`edit.${slot}_label`),
            m(
              'select.FormControl',
              { value: look[slot], onchange: set(slot) },
              this.data.options[slot].map((o) => m('option', { value: o }, t(`options.${o}`)))
            )
          )
        )
      ),

      m('.Festoon-row', [
        this.field(
          t('edit.trim_label'),
          m(
            'select.FormControl',
            {
              value: trimKind,
              onchange: (e: Event) => {
                const v = (e.target as HTMLSelectElement).value;
                this.change(id, 'trim', v === 'stripe' ? stripes : v);
              },
            },
            ['snow', 'stripe', 'none'].map((o) => m('option', { value: o }, t(`edit.trim_${o}`)))
          )
        ),
        trimKind === 'stripe' &&
          stripes.map((c, i) =>
            this.field(
              '',
              m('input.FormControl[type=color]', {
                value: c,
                oninput: (e: Event) => {
                  const next = [...stripes];
                  next[i] = (e.target as HTMLInputElement).value;
                  this.change(id, 'trim', next);
                },
              })
            )
          ),
      ]),

      m('.Festoon-actions', [
        look.group === 'custom'
          ? m(Button, { className: 'Button Button--danger', icon: 'fas fa-trash', onclick: () => this.remove(id) }, t('look.delete'))
          : this.overrides[id] && m(Button, { className: 'Button', icon: 'fas fa-undo', onclick: () => this.reset(id) }, t('look.reset')),
      ]),
    ]);
  }

  field(label: import('mithril').Children, control: import('mithril').Children) {
    return m('.Form-group.Festoon-field', [label && m('label', label), control]);
  }

  /** A month and a day, stored as "MM-DD" so the look repeats every year. */
  monthDay(value: string, onchange: (v: string) => void) {
    const [mm, dd] = (value || '01-01').split('-');
    const months = Array.from({ length: 12 }, (_, i) =>
      new Date(2001, i, 1).toLocaleDateString(app.translator.getLocale() || undefined, { month: 'long' })
    );
    const pad = (n: number) => String(n).padStart(2, '0');

    return m('.Festoon-monthDay', [
      m(
        'select.FormControl',
        { value: mm, onchange: (e: Event) => onchange(`${(e.target as HTMLSelectElement).value}-${dd}`) },
        months.map((name, i) => m('option', { value: pad(i + 1) }, name))
      ),
      m(
        'select.FormControl',
        { value: dd, onchange: (e: Event) => onchange(`${mm}-${(e.target as HTMLSelectElement).value}`) },
        Array.from({ length: 31 }, (_, i) => m('option', { value: pad(i + 1) }, i + 1))
      ),
    ]);
  }

  add() {
    const id = Math.random().toString(36).slice(2, 10);
    this.custom.push({ id, name: '', enabled: true, from: '01-01', to: '01-01', primary: '#536f90', secondary: '#536f90', trim: 'none' });
    this.attrs.custom(JSON.stringify(this.custom));
    // The server lists a look once it is saved; until then show it from here.
    this.data.looks.push({
      id: 'custom-' + id,
      group: 'custom',
      enabled: true,
      priority: 30,
      before: 0,
      after: 0,
      primary: '#536f90',
      secondary: '#536f90',
      garland: 'none',
      ornament: 'none',
      corner: 'none',
      hat: 'none',
      effect: 'none',
      trim: 'none',
      date: { type: 'range', from: '01-01', to: '01-01' },
    });
    this.open = 'custom-' + id;
  }

  remove(id: string) {
    this.custom = this.custom.filter((c) => 'custom-' + c.id !== id);
    this.attrs.custom(JSON.stringify(this.custom));
    this.open = null;
  }

  reset(id: string) {
    delete this.overrides[id];
    this.attrs.looks(JSON.stringify(this.overrides));
  }
}
