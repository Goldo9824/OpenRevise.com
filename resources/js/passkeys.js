/*
 | Admin passkeys (registration in Settings, and the second sign-in step),
 | using @simplewebauthn/browser.
 */

import { browserSupportsWebAuthn, startAuthentication, startRegistration } from '@simplewebauthn/browser';

window.openrevisePasskeys = {
    supported: browserSupportsWebAuthn,

    async register(optionsJSON) {
        const options = typeof optionsJSON === 'string' ? JSON.parse(optionsJSON) : optionsJSON;
        return JSON.stringify(await startRegistration({ optionsJSON: options }));
    },

    async authenticate(optionsJSON) {
        const options = typeof optionsJSON === 'string' ? JSON.parse(optionsJSON) : optionsJSON;
        return JSON.stringify(await startAuthentication({ optionsJSON: options }));
    },
};
