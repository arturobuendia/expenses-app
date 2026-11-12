import { ref } from 'vue';

/** Tamaño de lienzo de las cards compartibles (4:5, el que mejor cae en un teléfono). */
export const SHARE_CARD_WIDTH = 1080;
export const SHARE_CARD_HEIGHT = 1350;

export interface ShareImageOptions {
    /** Nombre del archivo sin extensión; se descarga como `{fileName}.png`. */
    fileName: string;
    title: string;
    text?: string;
}

export type ShareImageResult = 'shared' | 'downloaded' | 'cancelled';

/**
 * Convierte un nodo del DOM en PNG y lo comparte (Web Share API) o lo descarga.
 *
 * La card se renderiza siempre a tamaño real (1080x1350) y el preview la
 * escala con un `transform` en un contenedor aparte, así el export no depende
 * del tamaño visible en pantalla.
 */
export function useShareImage() {
    const isExporting = ref(false);
    const error = ref<string | null>(null);

    /**
     * `html-to-image` vuelve a descargar y serializar cada fuente en cada
     * exportación (~1s con las fuentes variables). Se cachea el CSS embebido
     * para que la segunda exportación sea inmediata.
     */
    let fontEmbedCSS: Promise<string> | null = null;

    async function renderPng(node: HTMLElement): Promise<Blob> {
        // Import dinámico: el módulo toca el DOM al evaluarse y este composable
        // también se ejecuta durante el SSR de Inertia.
        const { toBlob, getFontEmbedCSS } = await import('html-to-image');

        fontEmbedCSS ??= getFontEmbedCSS(node).catch(() => '');

        const blob = await toBlob(node, {
            width: SHARE_CARD_WIDTH,
            height: SHARE_CARD_HEIGHT,
            backgroundColor: '#09090b',
            pixelRatio: 2,
            fontEmbedCSS: await fontEmbedCSS,
        });

        if (!blob) {
            throw new Error('El navegador no pudo generar la imagen.');
        }

        return blob;
    }

    function downloadBlob(blob: Blob, fileName: string) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();

        // Safari cancela la descarga si la URL se revoca en el mismo tick.
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    async function run<T>(task: () => Promise<T>): Promise<T | null> {
        if (isExporting.value) {
            return null;
        }

        isExporting.value = true;
        error.value = null;

        try {
            return await task();
        } catch (cause) {
            console.error(cause);
            error.value =
                'No se pudo generar la imagen. Intenta de nuevo o toma una captura de pantalla.';

            return null;
        } finally {
            isExporting.value = false;
        }
    }

    async function share(
        node: HTMLElement,
        options: ShareImageOptions,
    ): Promise<ShareImageResult | null> {
        return run(async () => {
            const blob = await renderPng(node);
            const file = new File([blob], `${options.fileName}.png`, {
                type: 'image/png',
            });

            if (navigator.canShare?.({ files: [file] })) {
                try {
                    await navigator.share({
                        files: [file],
                        title: options.title,
                        text: options.text,
                    });

                    return 'shared' as const;
                } catch (cause) {
                    // Cerrar la hoja de compartir no es un fallo: el usuario
                    // decidió no compartir y no debe recibir un archivo.
                    if (
                        cause instanceof DOMException &&
                        cause.name === 'AbortError'
                    ) {
                        return 'cancelled' as const;
                    }

                    // Safari rechaza compartir archivos generados después de un
                    // `await`; caemos a descarga para no dejarlo sin nada.
                }
            }

            downloadBlob(blob, `${options.fileName}.png`);

            return 'downloaded' as const;
        });
    }

    async function download(
        node: HTMLElement,
        options: ShareImageOptions,
    ): Promise<ShareImageResult | null> {
        return run(async () => {
            downloadBlob(await renderPng(node), `${options.fileName}.png`);

            return 'downloaded' as const;
        });
    }

    async function copyText(text: string): Promise<boolean> {
        try {
            await navigator.clipboard.writeText(text);

            return true;
        } catch {
            return false;
        }
    }

    return {
        isExporting,
        error,
        share,
        download,
        copyText,
    };
}
