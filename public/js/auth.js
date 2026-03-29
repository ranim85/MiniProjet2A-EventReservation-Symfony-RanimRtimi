/**
 * auth.js — WebAuthn (Passkeys) via Web Crypto API native
 * Aucune dépendance externe requise.
 */

// 
// UTILITAIRES BASE64URL
// 

function bufferToBase64Url(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function base64UrlToBuffer(b64url) {
    const b64 = b64url.replace(/-/g, '+').replace(/_/g, '/');
    const pad = '='.repeat((4 - b64.length % 4) % 4);
    return Uint8Array.from(atob(b64 + pad), c => c.charCodeAt(0)).buffer;
}

// 
// VÉRIFICATION DU SUPPORT WEBAUTHN
// 

function isWebAuthnSupported() {
    return !!(window.PublicKeyCredential && navigator.credentials && navigator.credentials.create);
}

// 
// ENREGISTREMENT D'UNE PASSKEY
// 

async function registerPasskey(username, credentialName = 'Ma passkey') {

    if (!isWebAuthnSupported()) {
        throw new Error('WebAuthn non supporté par ce navigateur. Utilisez Chrome, Firefox ou Safari récent.');
    }

    // ── Étape 1 : obtenir les options du serveur ───────────────────────
    const optRes = await fetch('/api/auth/register/options', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ username, credentialName }),
    });

    if (!optRes.ok) {
        const err = await optRes.json();
        throw new Error(err.error || 'Erreur serveur lors de la récupération des options.');
    }

    const options = await optRes.json();

    // ── Étape 2 : créer la passkey (demande biométrie au navigateur) ───
    let credential;
    try {
        credential = await navigator.credentials.create({
            publicKey: {
                challenge:              base64UrlToBuffer(options.challenge),
                rp:                     options.rp,
                user: {
                    id:          base64UrlToBuffer(options.user.id),
                    name:        options.user.name,
                    displayName: options.user.displayName,
                },
                pubKeyCredParams:       options.pubKeyCredParams,
                timeout:                options.timeout,
                attestation:            options.attestation,
                excludeCredentials:     (options.excludeCredentials || []).map(c => ({
                    ...c, id: base64UrlToBuffer(c.id),
                })),
                authenticatorSelection: options.authenticatorSelection,
            },
        });
    } catch (err) {
        if (err.name === 'NotAllowedError') {
            throw new Error('Création de passkey annulée ou refusée par l\'utilisateur.');
        }
        if (err.name === 'InvalidStateError') {
            throw new Error('Une passkey existe déjà sur cet appareil pour ce compte.');
        }
        throw new Error('Erreur lors de la création : ' + err.message);
    }

    // ── Étape 3 : extraire la clé publique de la réponse ──────────────
    let publicKeyB64 = '';
    try {
        // getPublicKey() disponible dans les navigateurs modernes
        if (credential.response.getPublicKey) {
            const pkBuffer = credential.response.getPublicKey();
            if (pkBuffer) publicKeyB64 = bufferToBase64Url(pkBuffer);
        }
    } catch (_) {
        publicKeyB64 = credential.id; // Fallback : on utilise l'id
    }

    // ── Étape 4 : envoyer la réponse au serveur ────────────────────────
    const payload = {
        username,
        credentialName,
        credential: {
            id:    credential.id,
            rawId: bufferToBase64Url(credential.rawId),
            type:  credential.type,
            response: {
                clientDataJSON:    bufferToBase64Url(credential.response.clientDataJSON),
                attestationObject: bufferToBase64Url(credential.response.attestationObject),
                publicKey:         publicKeyB64,
            },
            clientExtensionResults: credential.getClientExtensionResults(),
        },
    };

    const verRes = await fetch('/api/auth/register/verify', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload),
    });

    const result = await verRes.json();
    if (!verRes.ok) throw new Error(result.error || 'Échec de la vérification côté serveur.');

    // ── Étape 5 : stocker les tokens JWT ──────────────────────────────
    if (result.token) {
        localStorage.setItem('jwt_token',     result.token);
        localStorage.setItem('refresh_token', result.refresh_token);
    }

    return result;
}

// 
// CONNEXION AVEC UNE PASSKEY EXISTANTE
// 

async function loginWithPasskey(username = '') {

    if (!isWebAuthnSupported()) {
        throw new Error('WebAuthn non supporté par ce navigateur.');
    }

    // ── Étape 1 : obtenir le challenge de connexion ────────────────────
    const optRes = await fetch('/api/auth/login/options', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ username }),
    });

    if (!optRes.ok) {
        const err = await optRes.json();
        throw new Error(err.error || 'Erreur lors de la récupération du challenge.');
    }

    const options = await optRes.json();

    // ── Étape 2 : demander la signature biométrique ────────────────────
    let assertion;
    try {
        assertion = await navigator.credentials.get({
            publicKey: {
                challenge:         base64UrlToBuffer(options.challenge),
                rpId:              options.rpId,
                timeout:           options.timeout,
                userVerification:  options.userVerification,
                allowCredentials:  (options.allowCredentials || []).map(c => ({
                    ...c, id: base64UrlToBuffer(c.id),
                })),
            },
        });
    } catch (err) {
        if (err.name === 'NotAllowedError') {
            throw new Error('Authentification annulée ou refusée.');
        }
        if (err.name === 'NotFoundError') {
            throw new Error('Aucune passkey trouvée sur cet appareil.');
        }
        throw new Error('Erreur biométrique : ' + err.message);
    }

    // ── Étape 3 : envoyer l'assertion au serveur ───────────────────────
    const payload = {
        credential: {
            id:    assertion.id,
            rawId: bufferToBase64Url(assertion.rawId),
            type:  assertion.type,
            response: {
                clientDataJSON:    bufferToBase64Url(assertion.response.clientDataJSON),
                authenticatorData: bufferToBase64Url(assertion.response.authenticatorData),
                signature:         bufferToBase64Url(assertion.response.signature),
                userHandle:        assertion.response.userHandle
                    ? bufferToBase64Url(assertion.response.userHandle)
                    : null,
            },
            clientExtensionResults: assertion.getClientExtensionResults(),
        },
    };

    const verRes = await fetch('/api/auth/login/verify', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload),
    });

    const result = await verRes.json();
    if (!verRes.ok) throw new Error(result.error || 'Échec de l\'authentification.');

    // ── Étape 4 : stocker les tokens JWT ──────────────────────────────
    if (result.token) {
        localStorage.setItem('jwt_token',     result.token);
        localStorage.setItem('refresh_token', result.refresh_token);
    }

    return result;
}

// 
// REQUÊTES API AVEC JWT
// 

function authFetch(url, options = {}) {
    const token = localStorage.getItem('jwt_token');
    return fetch(url, {
        ...options,
        headers: {
            'Content-Type':  'application/json',
            ...(options.headers || {}),
            'Authorization': token ? `Bearer ${token}` : '',
        },
    });
}

async function refreshJwtToken() {
    const refresh = localStorage.getItem('refresh_token');
    if (!refresh) return false;

    const res = await fetch('/api/token/refresh', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ refresh_token: refresh }),
    });

    if (!res.ok) {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('refresh_token');
        return false;
    }

    const data = await res.json();
    localStorage.setItem('jwt_token', data.token);
    if (data.refresh_token) localStorage.setItem('refresh_token', data.refresh_token);
    return true;
}

function logout() {
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('refresh_token');
    window.location.href = '/login';
}

function isLoggedIn() {
    return !!localStorage.getItem('jwt_token');
}