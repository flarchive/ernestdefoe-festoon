import app from 'flarum/admin/app';

// Settings are declared through the Admin extender in ./extend (Flarum 2).
app.initializers.add('ernestdefoe-festoon', () => {});

export { default as extend } from './extend';
