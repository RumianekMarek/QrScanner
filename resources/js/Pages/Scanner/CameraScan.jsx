import { Html5Qrcode } from "html5-qrcode";
import { useEffect, useRef } from "react";

export default function CameraScan({ onScan }) {
    const scannerRef = useRef(null);
    const containerRef = useRef(null);
    let uniqueQR = '';

    useEffect(() => {
        const timer = setTimeout(() => {
            if (!scannerRef.current && containerRef.current) {
                scannerRef.current = new Html5Qrcode("qr-reader");

                scannerRef.current.start(
                    { facingMode: "environment" },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                    },
                    (decodedText) => {
                        if (onScan && decodedText !== uniqueQR) {
                            uniqueQR = decodedText;
                            onScan(decodedText);
                        }
                    },
                    () => {}
                ).catch();
            }
        }, 1000);

        const stopScanner = async () => {
            try 
            {
                clearTimeout(timer);
                await scannerRef.current.stop()
                scannerRef.current = null;
                
            }
            catch (error)
            {
                console.warn(error);
            }
        };

        return () => {
            stopScanner();
        };
    }, []);

    return <div id="qr-reader" ref={containerRef} style={{ width: '100%', maxWidth: '600px' }} />;
}