import { Html5QrcodeScanner } from "html5-qrcode";
import { useEffect } from "react";

export default function CameraScan({ onScan }) {
    useEffect(() => {
        const qrCameraScan = new Html5QrcodeScanner("reader", {
            fps: 2,
            qrbox: { width: 250, height: 250 },
            videoConstraints: { facingMode: "environment"},
            rememberLastUsedCamera: false,
        });

        qrCameraScan.render(
            (decodedText, decodedResult) => {
                if (onScan) onScan(decodedText);
            },
            (error) => {
            }
        );

        return () => {
            qrCameraScan.clear();
        };
    }, [onScan]);

    return <div id="reader"></div>;
}
