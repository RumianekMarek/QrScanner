import { Html5Qrcode } from "html5-qrcode";
import { useEffect, useRef } from "react";

export default function CameraScan({ onScan }) {
    const scannerRef = useRef(null);
    const containerRef = useRef(null);
    let uniqueQR = '';

    useEffect(() => {
        const timer = setTimeout(() => {
            if (!scannerRef.current && containerRef.current) {
                // Tworzenie instancji skanera
                scannerRef.current = new Html5Qrcode("qr-reader");

                // Rozpoczęcie skanowania
                scannerRef.current.start(
                    { facingMode: "environment" },
                    {
                        fps: 10, // Ilość klatek na sekundę
                        qrbox: { width: 250, height: 250 },
                    },
                    (decodedText) => {
                        console.log("Previous QR:", uniqueQR);
                        console.log("Current QR:", decodedText);
    
                        if (onScan && decodedText !== uniqueQR) {
                            console.log('tak');
                            uniqueQR = decodedText; // Zapisz aktualny tekst
                            onScan(decodedText); // Wywołaj funkcję `onScan`
                        }
                    },
                    () => {}
                ).catch(console.error);
            }
        }, 2000); // Opóźnienie 2 sekundy

        // Czyszczenie przy odmontowaniu komponentu
        return () => {
            clearTimeout(timer); // Anulowanie timera, jeśli komponent zostanie odmontowany
            if (scannerRef.current) {
                scannerRef.current.stop()
                    .then(() => {
                        scannerRef.current = null;
                    })
                    .catch(console.error);
            }
        };
    }, []);

    return <div id="qr-reader" ref={containerRef} style={{ width: '100%', maxWidth: '600px' }} />;
}