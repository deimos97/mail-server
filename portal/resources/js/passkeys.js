// Passkeys (WebAuthn) con @simplewebauthn/browser. El servidor (spatie/laravel-passkeys) genera las
// opciones y valida la firma; aquí solo se le pide al navegador que cree o use la passkey.
import { browserSupportsWebAuthn, startAuthentication, startRegistration } from '@simplewebauthn/browser';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

export default function registerPasskeyComponents(Alpine) {
    // Mi cuenta → Seguridad → "Añadir una passkey"
    Alpine.data('passkeyCreate', (optionsUrl, storeUrl) => ({
        supported: browserSupportsWebAuthn(),
        name: '',
        busy: false,
        error: '',
        async create() {
            this.error = '';
            if (!this.name.trim()) { this.error = 'Ponle un nombre (por ejemplo, «iPhone de Ana»).'; return; }
            this.busy = true;
            try {
                const options = await (await fetch(optionsUrl, { headers: { Accept: 'application/json' } })).json();
                const passkey = await startRegistration({ optionsJSON: options });
                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ name: this.name.trim(), passkey: JSON.stringify(passkey) }),
                });
                if (!response.ok) throw new Error((await response.json()).message ?? 'error');
                location.reload();
            } catch (error) {
                this.error = error?.name === 'NotAllowedError'
                    ? 'Cancelado. Puedes intentarlo otra vez cuando quieras.'
                    : (error?.message && error.message !== 'error' ? error.message : 'No se ha podido crear la passkey.');
            } finally {
                this.busy = false;
            }
        },
    }));

    // /entrar → "Entrar con una passkey"
    Alpine.data('passkeyLogin', (optionsUrl) => ({
        supported: browserSupportsWebAuthn(),
        busy: false,
        error: '',
        async login() {
            this.error = '';
            this.busy = true;
            try {
                const options = await (await fetch(optionsUrl, { headers: { Accept: 'application/json' } })).json();
                const response = await startAuthentication({ optionsJSON: options });
                this.$refs.response.value = JSON.stringify(response);
                this.$refs.form.submit();
            } catch (error) {
                this.busy = false;
                this.error = error?.name === 'NotAllowedError' ? '' : 'No se ha podido entrar con la passkey.';
            }
        },
    }));
}
