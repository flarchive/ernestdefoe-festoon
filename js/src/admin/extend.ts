import app from 'flarum/admin/app';
import Admin from 'flarum/common/extenders/Admin';
import FestoonPanel from './FestoonPanel';

declare const m: import('mithril').Static;

type Streams = { setting: (key: string, fallback?: string) => (value?: string) => string };

export default [
  new Admin()
    .setting(() => ({
      setting: 'ernestdefoe-festoon.enabled',
      type: 'boolean',
      label: app.translator.trans('ernestdefoe-festoon.admin.enabled_label'),
    }))
    .customSetting(function (this: Streams) {
      return m(FestoonPanel, {
        timezone: this.setting('ernestdefoe-festoon.timezone', ''),
        parts: this.setting('ernestdefoe-festoon.parts', '{}'),
        looks: this.setting('ernestdefoe-festoon.looks', '{}'),
        custom: this.setting('ernestdefoe-festoon.custom', '[]'),
      });
    }),
];
