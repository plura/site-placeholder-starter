// Shared submit transport for the contact and newsletter forms. Only the parts that are
// genuinely identical live here — posting, parsing, and deciding whether it worked. What each
// form does to the page afterwards stays in its own module, because those legitimately differ.
//
// Delete this file only if both forms are gone; it survives removing either one.

// Where the PHP endpoints sit relative to THIS page, set per page in <html data-app-base>. A
// language version one directory down reaches them by a different path, and both forms share
// one copy of this module. Falls back to the root layout.
const APP_BASE = document.documentElement.dataset.appBase ?? 'starter/app/';

/**
 * POSTs a form to one of the starter/app/ endpoints.
 *
 * Never rejects: a dead connection or a non-JSON response resolves as a failure with an empty
 * message, so callers fall through to their own copy rather than showing the browser's raw
 * error text ("Failed to fetch") to a visitor.
 *
 * @param {string}   endpoint Filename under starter/app/, e.g. 'submit.php'.
 * @param {FormData} data     Fields to post. The page's language is added here.
 * @returns {Promise<{ok: boolean, message: string}>} `message` is the server's own copy, in the
 *          language the page was rendered in, and is empty when there was no response to read.
 */
export async function post(endpoint, data) {
    // Tells the endpoint which language to answer in (see starter/app/strings.php). Inert on a
    // single-language site, where strings.php just falls through to its base copy.
    data.set('lang', document.documentElement.lang);

    try {
        const res  = await fetch(APP_BASE + endpoint, { method: 'POST', body: data });
        // Guarded so an HTML error page (a 500 from the host, say) is treated as a failure
        // rather than throwing on the parse.
        const json = await res.json().catch(() => ({}));

        return { ok: res.ok && json.success === true, message: json.message ?? '' };
    } catch {
        return { ok: false, message: '' };
    }
}
