/**
 * WhatsApp share links. Without a number, WhatsApp asks which contact to send to.
 */
export function whatsAppUrl(text: string, phone?: string | null): string {
    return `https://wa.me/${phone ?? ''}?text=${encodeURIComponent(text)}`;
}

export async function copyText(text: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(text);

        return true;
    } catch {
        return false;
    }
}
