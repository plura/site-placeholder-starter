// Every user-facing string the JS renders, in one place — so translating a project means
// editing this file rather than hunting through the modules. Consumers import the resolved
// set and never learn how many languages exist, so adding or removing a language never
// changes their copy. (Their endpoint paths are a separate matter — a page one directory
// down needs data-app-base; see modal.js.)
//
// Keys are semantic ('submit'), not the English source text as in gettext/.po. The copy here
// is placeholder text that every project rewrites, and source-string keys would silently go
// stale the moment one did — a renamed button leaves a dead key that falls back to the new
// English on the translated page.

// Default-language copy — the only complete set, and the fallback for anything a translation
// below leaves out.
const BASE = {
    submit:     'Send',
    submitting: 'Sending…',
    success:    'Message sent. We\'ll be in touch shortly.',
    error:      'Could not send. Please try again, or email us.',

    // Language-coupled: strips the optional-field marker from a <label> before the text is
    // posted as a field name (see modal.js). Lives here because it must change with the copy —
    // left loose in modal.js it silently stops matching the moment the labels are translated.
    optionalSuffix: /\s*\(optional\)\s*$/i,

    // OPTIONAL: mailing list — remove these two if this project has no newsletter. Only used
    // when the server sends no message of its own.
    subscribeSuccess: 'Thanks for subscribing.',
    subscribeError:   'Could not subscribe. Please try again.',
    // /OPTIONAL
};

// OPTIONAL (Tier 2): translations, as a delta against BASE — only keys that differ need
// listing. Remove this whole block for a single-language site. To change the default
// language, rewrite BASE in the new language and move the old copy down here.
const OVERRIDES = {
    pt: {
        submit:     'Enviar',
        submitting: 'A enviar…',
        success:    'Mensagem enviada. Entraremos em contacto em breve.',
        error:      'Erro ao enviar. Tente novamente ou contacte-nos por email.',

        // Must track the marker actually used in pt/index.html's labels — see BASE.
        optionalSuffix: /\s*\(opcional\)\s*$/i,

        // OPTIONAL: mailing list.
        subscribeSuccess: 'Obrigado por subscrever.',
        subscribeError:   'Não foi possível subscrever. Tente novamente.',
        // /OPTIONAL
    },
};
// /OPTIONAL

// Normalizes 'en-GB' → 'en'. An unknown or absent lang falls through to BASE.
const lang = document.documentElement.lang.split('-')[0].toLowerCase();

export default { ...BASE, ...(OVERRIDES[lang] ?? {}) };
