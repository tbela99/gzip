/**
 *
 * @param {string} data
 * @param {string}  key
 * @param {string}  hash
 * @returns {Promise<string>}
 */
export async function hash_mac(data, key, hash = 'SHA-256') {

    const keyBytes = new TextEncoder().encode(data);
    const messageBytes = new TextEncoder().encode(key);

    const cryptoKey = await crypto.subtle.importKey(
        'raw', keyBytes, { name: 'HMAC', hash },
        true, ['sign']
    );
    const sig = await crypto.subtle.sign('HMAC', cryptoKey, messageBytes);

    return [...new Uint8Array(sig)].map(b => b.toString(16).padStart(2, '0')).join('');
}