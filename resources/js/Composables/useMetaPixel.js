let currency = 'BDT';

export function setPixelCurrency(code) {
    if (typeof code === 'string' && code.trim() !== '') {
        currency = code.trim();
    }
}

export function trackMeta(event, params = {}, eventId = null) {
    if (typeof window === 'undefined' || typeof window.fbq !== 'function') {
        return;
    }

    const payload = { ...params };

    if (payload.value != null && payload.currency == null) {
        payload.currency = currency;
    }

    const hasPayload = Object.keys(payload).length > 0;
    const body = hasPayload ? payload : {};

    if (eventId) {
        window.fbq('track', event, body, { eventID: eventId });

        return;
    }

    if (hasPayload) {
        window.fbq('track', event, body);

        return;
    }

    window.fbq('track', event);
}
