/*
 * Messenger E2E — client-side AES-256-GCM encryption.
 *
 * Architecture:
 *   - Passphrase (NEVER sent to server in plaintext)
 *       PBKDF2(passphrase, salt = "messenger-e2e-v1|member_id", 150_000, SHA-256)
 *         -> 256-bit master key + 64-hex client hash (for server verification only)
 *   - Per-thread key (HKDF over master key, info = "thread:<id>")
 *   - encrypt: AES-GCM-256 (iv = 12 random bytes), output = base64(ciphertext+tag)
 *   - decrypt: same
 *
 * Key storage:
 *   - master key kept ONLY in memory (window-scoped). Re-derive on each session
 *     (member re-types passphrase). This is the safest tradeoff for browser-side.
 *
 * Server invariant:
 *   - Server stores ciphertext + iv + key_fingerprint, but NO key material.
 *   - Server cannot decrypt — by design.
 */
(function (global) {
    'use strict';

    if (!global.crypto || !global.crypto.subtle) {
        global.MessengerE2E = { unsupported: true };
        return;
    }

    var enc = new TextEncoder();
    var dec = new TextDecoder();

    function b64encode(buffer) {
        var bytes = new Uint8Array(buffer);
        var binary = '';
        for (var i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }

    function b64decode(b64) {
        var binary = atob(b64);
        var bytes = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    }

    function bufToHex(buffer) {
        var bytes = new Uint8Array(buffer);
        var hex = '';
        for (var i = 0; i < bytes.length; i++) {
            var h = bytes[i].toString(16);
            if (h.length === 1) h = '0' + h;
            hex += h;
        }
        return hex;
    }

    /**
     * Derive a CryptoKey from the passphrase via PBKDF2(SHA-256, 150k).
     * Returns { masterBits: ArrayBuffer, clientHash: string-hex } where clientHash is
     * a SEPARATE PBKDF2 output (different salt prefix) used only for server verification.
     */
    async function deriveMasterKey(passphrase, memberId) {
        var saltText = 'messenger-e2e-v1|' + (memberId | 0);
        var salt = enc.encode(saltText);

        var baseKey = await global.crypto.subtle.importKey(
            'raw',
            enc.encode(passphrase),
            { name: 'PBKDF2' },
            false,
            ['deriveBits', 'deriveKey']
        );

        // 256-bit master key bits. We derive RAW bits so we can subsequently HKDF per-thread.
        var keyBits = await global.crypto.subtle.deriveBits(
            { name: 'PBKDF2', salt: salt, iterations: 150000, hash: 'SHA-256' },
            baseKey,
            256
        );

        // Separate 256-bit hash for server verification (different salt prefix).
        var hashBits = await global.crypto.subtle.deriveBits(
            { name: 'PBKDF2', salt: enc.encode('server-verify|' + saltText), iterations: 150000, hash: 'SHA-256' },
            baseKey,
            256
        );

        return {
            masterBits: keyBits,
            clientHash: bufToHex(hashBits)
        };
    }

    /**
     * HKDF-Expand-like derivation per thread from master key bits.
     * Simplified: HMAC-SHA-256(masterBits, "thread:<id>"). 256 bits -> AES-GCM CryptoKey.
     */
    async function deriveThreadKey(masterBits, threadId) {
        var hmacKey = await global.crypto.subtle.importKey(
            'raw',
            masterBits,
            { name: 'HMAC', hash: 'SHA-256' },
            false,
            ['sign']
        );
        var info = enc.encode('thread:' + (threadId | 0));
        var keyMaterial = await global.crypto.subtle.sign('HMAC', hmacKey, info);

        var aesKey = await global.crypto.subtle.importKey(
            'raw',
            keyMaterial,
            { name: 'AES-GCM' },
            false,
            ['encrypt', 'decrypt']
        );

        // Fingerprint = first 16 bytes (32 hex chars) of SHA-256(keyMaterial).
        var fp = await global.crypto.subtle.digest('SHA-256', keyMaterial);
        var fpHex = bufToHex(fp).substring(0, 32);

        return { key: aesKey, fingerprint: fpHex };
    }

    /**
     * MessengerE2E — public API.
     */
    function MessengerE2E(memberId) {
        this.memberId = memberId | 0;
        this.masterBits = null;
        this.clientHash = null;
        this._threadCache = Object.create(null);
    }

    MessengerE2E.prototype.isUnlocked = function () {
        return !!this.masterBits;
    };

    /**
     * Setup / unlock with a passphrase. Returns the server-side client hash
     * (hex) to be POSTed during setup or used to validate decryption attempts.
     */
    MessengerE2E.prototype.unlock = async function (passphrase) {
        if (typeof passphrase !== 'string' || passphrase.length < 8) {
            throw new Error('Passphrase must be at least 8 characters.');
        }
        var derived = await deriveMasterKey(passphrase, this.memberId);
        this.masterBits = derived.masterBits;
        this.clientHash = derived.clientHash;
        this._threadCache = Object.create(null);
        return derived.clientHash;
    };

    MessengerE2E.prototype.lock = function () {
        this.masterBits = null;
        this.clientHash = null;
        this._threadCache = Object.create(null);
    };

    MessengerE2E.prototype._getThreadKey = async function (threadId) {
        if (!this.masterBits) {
            throw new Error('E2E locked — unlock with passphrase first.');
        }
        var cached = this._threadCache[threadId];
        if (cached) return cached;
        var t = await deriveThreadKey(this.masterBits, threadId);
        this._threadCache[threadId] = t;
        return t;
    };

    MessengerE2E.prototype.fingerprintForThread = async function (threadId) {
        var t = await this._getThreadKey(threadId);
        return t.fingerprint;
    };

    MessengerE2E.prototype.encrypt = async function (threadId, plaintext) {
        if (typeof plaintext !== 'string' || plaintext === '') {
            throw new Error('Plaintext empty.');
        }
        var t = await this._getThreadKey(threadId);
        var iv = global.crypto.getRandomValues(new Uint8Array(12));
        var ct = await global.crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv },
            t.key,
            enc.encode(plaintext)
        );
        return {
            ciphertext: b64encode(ct),
            meta: {
                iv: b64encode(iv),
                alg: 'AES-GCM-256',
                key_fingerprint: t.fingerprint
            }
        };
    };

    MessengerE2E.prototype.decrypt = async function (threadId, ciphertextB64, meta) {
        if (!meta || !meta.iv) {
            throw new Error('Missing iv in ciphertext_meta.');
        }
        var t = await this._getThreadKey(threadId);
        if (meta.key_fingerprint && meta.key_fingerprint !== t.fingerprint) {
            // Mismatch indicates wrong passphrase or another user encrypted it
            // with a different key — refuse rather than show garbage.
            throw new Error('Key fingerprint mismatch — cannot decrypt.');
        }
        var iv = b64decode(meta.iv);
        var ct = b64decode(ciphertextB64);
        var pt = await global.crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: iv },
            t.key,
            ct
        );
        return dec.decode(pt);
    };

    global.MessengerE2E = MessengerE2E;
})(typeof window !== 'undefined' ? window : globalThis);
