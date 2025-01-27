import { Html5Qrcode } from "html5-qrcode";
import { useEffect, useRef } from "react";

export default function CameraScan({ onScan }) {
    const scannerRef = useRef(null);
    const containerRef = useRef(null);

    useEffect(() => {
        if (!scannerRef.current && containerRef.current) {
            // Create scanner instance
            scannerRef.current = new Html5Qrcode("qr-reader");
            // Start scanning
            scannerRef.current.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                },
                (decodedText) => {
                    if (onScan) onScan(decodedText);
                },
                () => {} // Ignore errors
            ).catch(console.error);
        }

        // Cleanup
        return () => {
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